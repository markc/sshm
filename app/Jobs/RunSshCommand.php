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
        public bool $useBashMode = false
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

            // Create the process
            $process = Process::start($sshCommand, function (string $type, string $output) {
                // Stream all output in real-time
                $outputType = $type === SymfonyProcess::ERR ? 'err' : 'out';

                // Split output into lines for better terminal display
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (! empty(trim($line))) {
                        SshOutputReceived::dispatch($this->processId, $outputType, $line);
                    }
                }
            });

            // Store the PID for process control
            Cache::put("process:{$this->processId}:pid", $process->id(), now()->addHours(2));

            // Wait for process completion
            $result = $process->wait();

            // Broadcast completion status
            if ($result->successful()) {
                SshOutputReceived::dispatch(
                    $this->processId,
                    'status',
                    '✅ Command completed successfully (Exit Code: 0)'
                );
            } else {
                SshOutputReceived::dispatch(
                    $this->processId,
                    'status',
                    "❌ Command failed (Exit Code: {$result->exitCode()})"
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
