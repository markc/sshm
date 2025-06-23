<?php

namespace App\Jobs;

use App\Events\SshOutputReceived;
use App\Models\SshHost;
use App\Services\SshService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class RunSshCommand implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600; // 1 hour max execution time

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $command,
        public string $processId,
        public int $userId,
        public int $hostId,
        public bool $useBashMode = false,
        public bool $fastMode = true  // New: prioritize speed over real-time streaming
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $host = SshHost::findOrFail($this->hostId);

            // Broadcast initial status
            SshOutputReceived::dispatch($this->processId, 'status', "Starting SSH connection to {$host->name}...");

            // Use the SSH service to build the command
            $sshService = app(SshService::class);
            $sshCommand = $this->buildSshCommand($host, $sshService);

            $modeMessage = $this->useBashMode ? "Executing with bash -ci: {$this->command}" : "Executing: {$this->command}";
            SshOutputReceived::dispatch($this->processId, 'status', $modeMessage);

            // Start execution timing
            $startTime = microtime(true);

            if ($this->fastMode) {
                // Fast mode: Execute command and send all output at once
                $result = Process::run($sshCommand);

                // Send all output at once for maximum speed
                if ($result->output()) {
                    $lines = explode("\n", trim($result->output()));
                    foreach ($lines as $line) {
                        if (! empty(trim($line))) {
                            SshOutputReceived::dispatch($this->processId, 'out', $line);
                        }
                    }
                }

                if ($result->errorOutput()) {
                    $lines = explode("\n", trim($result->errorOutput()));
                    foreach ($lines as $line) {
                        if (! empty(trim($line))) {
                            SshOutputReceived::dispatch($this->processId, 'err', $line);
                        }
                    }
                }
            } else {
                // Streaming mode: Buffer for collecting output chunks
                $outputBuffer = '';
                $errorBuffer = '';
                $lastFlushTime = microtime(true);
                $flushInterval = 0.05; // Flush every 50ms for better responsiveness

                // Create the process
                $process = Process::start($sshCommand, function (string $type, string $output) use (&$outputBuffer, &$errorBuffer, &$lastFlushTime, $flushInterval) {
                    $currentTime = microtime(true);
                    $outputType = $type === SymfonyProcess::ERR ? 'err' : 'out';

                    // Accumulate output in buffers
                    if ($outputType === 'err') {
                        $errorBuffer .= $output;
                    } else {
                        $outputBuffer .= $output;
                    }

                    // Flush buffers if enough time has passed or buffer is large
                    $timeSinceFlush = $currentTime - $lastFlushTime;
                    $shouldFlush = $timeSinceFlush >= $flushInterval ||
                                  strlen($outputBuffer) > 512 ||
                                  strlen($errorBuffer) > 512;

                    if ($shouldFlush) {
                        $this->flushBuffers($outputBuffer, $errorBuffer);
                        $lastFlushTime = $currentTime;
                    }
                });

                // Store the PID for process control
                Cache::put("process:{$this->processId}:pid", $process->id(), now()->addHours(2));

                // Wait for process completion
                $result = $process->wait();

                // Flush any remaining buffered output
                $this->flushBuffers($outputBuffer, $errorBuffer);
            }

            // Calculate execution time
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $executionTimeFormatted = $this->formatExecutionTime($executionTime);

            // Broadcast completion status with timing
            if ($result->successful()) {
                SshOutputReceived::dispatch(
                    $this->processId,
                    'status',
                    "✅ Command completed successfully (Exit Code: 0) - Execution time: {$executionTimeFormatted}"
                );
            } else {
                SshOutputReceived::dispatch(
                    $this->processId,
                    'status',
                    "❌ Command failed (Exit Code: {$result->exitCode()}) - Execution time: {$executionTimeFormatted}"
                );

                // Also broadcast any error output
                if ($result->errorOutput()) {
                    SshOutputReceived::dispatch($this->processId, 'err', $result->errorOutput());
                }
            }

        } catch (\Exception $e) {
            Log::error('SSH Command execution failed', [
                'process_id' => $this->processId,
                'host_id' => $this->hostId,
                'command' => $this->command,
                'error' => $e->getMessage(),
            ]);

            SshOutputReceived::dispatch(
                $this->processId,
                'err',
                "❌ Error: {$e->getMessage()}"
            );
        } finally {
            // Clean up cache keys
            Cache::forget("process:{$this->processId}:user");
            Cache::forget("process:{$this->processId}:host");
            Cache::forget("process:{$this->processId}:pid");

            SshOutputReceived::dispatch($this->processId, 'status', '--- Session ended ---');
        }
    }

    /**
     * Build the SSH command using the SSH service logic
     */
    private function buildSshCommand(SshHost $host, SshService $sshService): string
    {
        $sshOptions = [
            '-t', // Allocate pseudo-terminal for interactive commands
            '-o ConnectTimeout=10',
            '-o ServerAliveInterval=30',
            '-o ServerAliveCountMax=3',
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o LogLevel=ERROR', // Reduce SSH noise
        ];

        // Add port if not default
        if ($host->port && $host->port != 22) {
            $sshOptions[] = "-p {$host->port}";
        }

        // Add identity file if specified
        if ($host->identity_file) {
            $keyPath = $this->getKeyPath($host->identity_file);
            if ($keyPath) {
                $sshOptions[] = "-i {$keyPath}";
            }
        }

        // Build the command based on bash mode
        if ($this->useBashMode) {
            // Use bash -ci to load .bashrc with aliases and functions (like sx function)
            $wrappedCommand = sprintf('bash -ci %s', escapeshellarg($this->command));
        } else {
            // Set TERM environment variable for better compatibility
            $wrappedCommand = sprintf('TERM=xterm-256color %s', $this->command);
        }

        $sshCommand = sprintf(
            'ssh %s %s@%s %s',
            implode(' ', $sshOptions),
            escapeshellarg($host->user),
            escapeshellarg($host->hostname),
            escapeshellarg($wrappedCommand)
        );

        return $sshCommand;
    }

    /**
     * Flush accumulated output buffers to WebSocket
     */
    private function flushBuffers(string &$outputBuffer, string &$errorBuffer): void
    {
        // Process standard output buffer
        if (! empty($outputBuffer)) {
            // Send as complete lines for better terminal display
            $lines = explode("\n", trim($outputBuffer));
            foreach ($lines as $line) {
                if (! empty(trim($line))) {
                    SshOutputReceived::dispatch($this->processId, 'out', $line);
                }
            }
            $outputBuffer = '';
        }

        // Process error output buffer
        if (! empty($errorBuffer)) {
            $lines = explode("\n", trim($errorBuffer));
            foreach ($lines as $line) {
                if (! empty(trim($line))) {
                    SshOutputReceived::dispatch($this->processId, 'err', $line);
                }
            }
            $errorBuffer = '';
        }
    }

    /**
     * Format execution time in a human-readable format
     */
    private function formatExecutionTime(float $seconds): string
    {
        if ($seconds < 0.001) {
            return number_format($seconds * 1000000, 0) . 'μs';
        } elseif ($seconds < 1) {
            return number_format($seconds * 1000, 1) . 'ms';
        } elseif ($seconds < 60) {
            return number_format($seconds, 3) . 's';
        } else {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;

            return sprintf('%dm %.3fs', $minutes, $remainingSeconds);
        }
    }

    /**
     * Get the path to the SSH key file
     */
    private function getKeyPath(string $keyName): ?string
    {
        $sshService = app(SshService::class);
        $settings = app(\App\Settings\SshSettings::class);
        $keyPath = $settings->getHomeDir() . "/.ssh/{$keyName}";

        return file_exists($keyPath) ? $keyPath : null;
    }
}
