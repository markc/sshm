<?php

namespace App\Http\Controllers;

use App\Models\SshHost;
use App\Services\SshConnectionPoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Process\Process as SymfonyProcess;

class OptimizedSshController extends Controller
{
    private SshConnectionPoolService $connectionPool;

    public function __construct(SshConnectionPoolService $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Execute SSH command with real-time streaming via Server-Sent Events
     * This bypasses the queue system for maximum performance
     */
    public function streamCommand(Request $request): StreamedResponse
    {
        $request->validate([
            'command' => 'required|string|max:10000',
            'host_id' => 'required|exists:ssh_hosts,id',
            'use_bash' => 'boolean',
        ]);

        $hostId = $request->integer('host_id');
        $command = $request->string('command');
        $useBash = $request->boolean('use_bash', false);
        $processId = (string) Str::uuid();

        // Get SSH host configuration
        $host = SshHost::findOrFail($hostId);

        return new StreamedResponse(function () use ($host, $command, $useBash, $processId) {
            // Temporarily disable caching to debug command duplication issue
            $cacheKey = null;

            // Send initial connection message
            $this->sendSSE('status', "🚀 Connecting to {$host->name}...", $processId);

            try {
                $startTime = microtime(true);

                // Always use optimized execution (no more fast/streaming modes)
                $this->executeOptimized($host, $command, $useBash, $processId, $startTime, $cacheKey);

            } catch (\Exception $e) {
                Log::error('SSH Command execution failed', [
                    'host_id' => $host->id,
                    'command' => $command->value,
                    'error' => $e->getMessage(),
                ]);

                $this->sendSSE('error', "❌ Error: {$e->getMessage()}", $processId);
            } finally {
                $this->sendSSE('status', '--- Session ended ---', $processId);
                $this->sendSSE('complete', '', $processId);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ]);
    }

    /**
     * Optimized execution: Real-time streaming with intelligent buffering for best performance
     */
    private function executeOptimized(SshHost $host, string $command, bool $useBash, string $processId, float $startTime, ?string $cacheKey = null): void
    {
        $sshCommand = $this->buildSshCommand($host, $command, $useBash);

        // Use connection pooling for better performance
        $connection = $this->connectionPool->getConnection($host);

        $modeMessage = $useBash ? "Executing with bash: {$command}" : "Executing: {$command}";
        $this->sendSSE('status', $modeMessage, $processId);

        $outputBuffer = '';
        $errorBuffer = '';
        $lastFlushTime = microtime(true);
        $flushInterval = 0.01; // Ultra-optimized to 10ms for real-time response

        // For caching and line management
        $allOutputLines = [];
        $allErrorLines = [];
        $lineBuffer = '';
        $errorLineBuffer = '';

        // Start process with ultra-optimized real-time callback
        $process = Process::start($sshCommand, function (string $type, string $output) use (&$outputBuffer, &$errorBuffer, &$lastFlushTime, $flushInterval, $processId, &$allOutputLines, &$allErrorLines, &$lineBuffer, &$errorLineBuffer) {
            $currentTime = microtime(true);
            $outputType = $type === SymfonyProcess::ERR ? 'error' : 'output';

            // Process output character by character for real-time streaming
            if ($outputType === 'error') {
                $errorLineBuffer .= $output;
                $errorBuffer .= $output;

                // Process complete lines immediately for better UX
                while (($pos = strpos($errorLineBuffer, "\n")) !== false) {
                    $line = substr($errorLineBuffer, 0, $pos);
                    $errorLineBuffer = substr($errorLineBuffer, $pos + 1);
                    // Send ALL error lines including empty ones, only filter SSH warnings
                    if (! $this->shouldFilterLine($line)) {
                        $allErrorLines[] = $line;
                        $this->sendSSE('error', $line, $processId);
                    }
                }
            } else {
                $lineBuffer .= $output;
                $outputBuffer .= $output;

                // Process complete lines immediately
                while (($pos = strpos($lineBuffer, "\n")) !== false) {
                    $line = substr($lineBuffer, 0, $pos);
                    $lineBuffer = substr($lineBuffer, $pos + 1);
                    // Send ALL lines including empty ones for proper formatting, only filter SSH warnings
                    if (! $this->shouldFilterLine($line)) {
                        $allOutputLines[] = $line;
                        $this->sendSSE('output', $line, $processId);
                    }
                }
            }

            // Ultra-fast flushing with smaller buffers and adaptive timing
            $timeSinceFlush = $currentTime - $lastFlushTime;
            $bufferSize = strlen($outputBuffer) + strlen($errorBuffer);

            // Adaptive flushing based on buffer size and time
            $shouldFlush = $timeSinceFlush >= $flushInterval ||
                          $bufferSize > 128 ||  // Even smaller buffer for instant response
                          str_contains($output, "\n"); // Flush on newlines for better formatting

            if ($shouldFlush) {
                $lastFlushTime = $currentTime;

                // Force immediate browser rendering
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
        });

        // Wait for process completion
        $result = $process->wait();

        // Send any final partial lines that weren't flushed during execution
        if (! empty($lineBuffer) && ! $this->shouldFilterLine($lineBuffer)) {
            $allOutputLines[] = $lineBuffer;
            $this->sendSSE('output', $lineBuffer, $processId);
        }
        if (! empty($errorLineBuffer) && ! $this->shouldFilterLine($errorLineBuffer)) {
            $allErrorLines[] = $errorLineBuffer;
            $this->sendSSE('error', $errorLineBuffer, $processId);
        }

        // Cache successful commands for performance
        if ($cacheKey && $result->successful() && $this->isCacheableCommand($command)) {
            $executionTime = microtime(true) - $startTime;
            $cacheData = [
                'output_lines' => array_filter($allOutputLines, fn ($line) => ! empty(trim($line))),
                'exit_code' => $result->exitCode(),
                'execution_time' => $this->formatExecutionTime($executionTime),
                'timestamp' => date('Y-m-d H:i:s'),
                'host_id' => $host->id,
                'command' => $command,
            ];

            // Cache with intelligent TTL based on command type
            $ttl = $this->getCacheTtl($command);
            Redis::setex($cacheKey, $ttl, json_encode($cacheData));

            // Store cache metadata for monitoring
            Redis::hincrby('sshm:cache_stats', 'cache_sets', 1);
            Redis::hincrby('sshm:cache_stats', "cache_sets_ttl_{$ttl}", 1);
        }

        $this->sendExecutionComplete($result, $startTime, $processId);
    }

    /**
     * Build optimized SSH command with connection multiplexing
     */
    private function buildSshCommand(SshHost $host, string $command, bool $useBash): string
    {
        $sshOptions = [
            '-o ConnectTimeout=5',          // Faster timeout
            '-o ServerAliveInterval=30',
            '-o ServerAliveCountMax=2',     // Reduced for faster failure detection
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-o LogLevel=ERROR',
            '-o BatchMode=yes',             // Non-interactive for speed
        ];

        // Enable SSH multiplexing for connection reuse
        $controlPath = sys_get_temp_dir() . '/ssh-' . md5($host->hostname . $host->user);
        $sshOptions[] = '-o ControlMaster=auto';
        $sshOptions[] = "-o ControlPath={$controlPath}";
        $sshOptions[] = '-o ControlPersist=60s';  // Keep connection alive for 60s

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
        if ($useBash) {
            // Use bash -ci to load .bashrc with aliases and functions
            $wrappedCommand = sprintf('bash -ci %s', escapeshellarg($command));
        } else {
            // Set optimized environment variables
            $wrappedCommand = sprintf('TERM=xterm-256color LC_ALL=C %s', $command);
        }

        return sprintf(
            'ssh %s %s@%s %s',
            implode(' ', $sshOptions),
            escapeshellarg($host->user),
            escapeshellarg($host->hostname),
            escapeshellarg($wrappedCommand)
        );
    }

    /**
     * Send Server-Sent Event to client
     */
    private function sendSSE(string $type, string $data, string $processId): void
    {
        $event = [
            'type' => $type,
            'data' => $data,
            'process_id' => $processId,
            'timestamp' => microtime(true),
        ];

        echo 'data: ' . json_encode($event) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();

        // Small delay to prevent overwhelming the client
        usleep(1000); // 1ms
    }

    /**
     * Flush accumulated output buffers
     */
    private function flushBuffers(string &$outputBuffer, string &$errorBuffer, string $processId): void
    {
        // Process standard output buffer
        if (! empty($outputBuffer)) {
            $lines = explode("\n", rtrim($outputBuffer, "\n"));
            foreach ($lines as $line) {
                if (strlen(trim($line)) > 0) {
                    $this->sendSSE('output', $line, $processId);
                }
            }
            $outputBuffer = '';
        }

        // Process error output buffer
        if (! empty($errorBuffer)) {
            $lines = explode("\n", rtrim($errorBuffer, "\n"));
            foreach ($lines as $line) {
                if (strlen(trim($line)) > 0) {
                    $this->sendSSE('error', $line, $processId);
                }
            }
            $errorBuffer = '';
        }
    }

    /**
     * Send execution completion message with timing
     */
    private function sendExecutionComplete($result, float $startTime, string $processId): void
    {
        $executionTime = microtime(true) - $startTime;
        $executionTimeFormatted = $this->formatExecutionTime($executionTime);

        if ($result->successful()) {
            $this->sendSSE('status',
                "✅ Command completed successfully (Exit Code: 0) - Execution time: {$executionTimeFormatted}",
                $processId
            );
        } else {
            $this->sendSSE('status',
                "❌ Command failed (Exit Code: {$result->exitCode()}) - Execution time: {$executionTimeFormatted}",
                $processId
            );
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
        $settings = app(\App\Settings\SshSettings::class);
        $keyPath = $settings->getHomeDir() . "/.ssh/{$keyName}";

        return file_exists($keyPath) ? $keyPath : null;
    }

    /**
     * Check if a command is safe to cache with intelligent analysis
     */
    private function isCacheableCommand(string $command): bool
    {
        $command = strtolower(trim($command));

        // Commands that are definitely safe to cache (read-only)
        $safeCommands = [
            'ls', 'll', 'pwd', 'whoami', 'id', 'date', 'uptime', 'uname',
            'df', 'free', 'ps', 'top', 'htop', 'netstat', 'ss', 'lsof',
            'cat', 'head', 'tail', 'grep', 'find', 'locate', 'which',
            'echo', 'hostname', 'ifconfig', 'ip', 'route', 'ping',
            'systemctl status', 'service status', 'docker ps', 'docker images',
            'file', 'stat', 'wc', 'sort', 'uniq', 'cut', 'awk', 'sed',
            'env', 'printenv', 'groups', 'history', 'whereis',
        ];

        // Commands that are never safe to cache (modify system state)
        $unsafeCommands = [
            'rm', 'rmdir', 'mv', 'cp', 'mkdir', 'touch', 'chmod',
            'chown', 'ln', 'kill', 'killall', 'pkill', 'sudo',
            'su', 'passwd', 'usermod', 'userdel', 'useradd',
            'systemctl start', 'systemctl stop', 'systemctl restart',
            'service start', 'service stop', 'service restart',
            'mount', 'umount', 'fdisk', 'mkfs', 'dd', 'tar', 'zip',
        ];

        // Check unsafe commands first (more important)
        foreach ($unsafeCommands as $unsafeCmd) {
            if (str_starts_with($command, $unsafeCmd)) {
                return false;
            }
        }

        // Check safe commands
        foreach ($safeCommands as $safeCmd) {
            if (str_starts_with($command, $safeCmd)) {
                return true;
            }
        }

        // Additional heuristics for safety
        if (str_contains($command, '>') || str_contains($command, '>>') ||
            (str_contains($command, '|') && (str_contains($command, 'tee') || str_contains($command, 'xargs')))) {
            return false; // Likely modifying files
        }

        return false; // Default to not caching unknown commands
    }

    /**
     * Filter out common SSH/bash warning messages that don't add value
     */
    private function shouldFilterLine(string $line): bool
    {
        $line = trim($line);

        // Filter common bash/SSH warnings
        $filtersPatterns = [
            'bash: cannot set terminal process group',
            'bash: no job control in this shell',
            'cannot set terminal process group',
            'no job control in this shell',
            'Inappropriate ioctl for device',
            'stdin: is not a tty',
        ];

        foreach ($filtersPatterns as $pattern) {
            if (str_contains($line, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cache TTL based on command type for intelligent caching
     */
    private function getCacheTtl(string $command): int
    {
        $command = strtolower(trim($command));

        // Commands that change very frequently (short cache)
        if (str_contains($command, 'ps') || str_contains($command, 'top') ||
            str_contains($command, 'free') || str_contains($command, 'netstat') ||
            str_contains($command, 'ss') || str_contains($command, 'lsof')) {
            return 15; // 15 seconds
        }

        // Commands that change moderately (medium cache)
        if (str_contains($command, 'df') || str_contains($command, 'du') ||
            str_contains($command, 'systemctl status') || str_contains($command, 'docker')) {
            return 60; // 1 minute
        }

        // Commands that change rarely (long cache)
        if (str_contains($command, 'uname') || str_contains($command, 'hostname') ||
            str_contains($command, 'whoami') || str_contains($command, 'which') ||
            str_contains($command, 'id') || str_contains($command, 'groups')) {
            return 1800; // 30 minutes
        }

        // Default cache time for file listings and content
        return 300; // 5 minutes
    }

    /**
     * Get cached command results for performance optimization
     */
    public function getCachedResult(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'host_id' => 'required|exists:ssh_hosts,id',
        ]);

        $cacheKey = 'sshm:cmd_cache:' . md5($request->input('host_id') . ':' . $request->input('command'));
        $cached = Redis::get($cacheKey);

        if ($cached) {
            $cachedData = json_decode($cached, true);

            return response()->json([
                'cached' => true,
                'result' => $cachedData,
                'timestamp' => $cachedData['timestamp'],
            ]);
        }

        return response()->json(['cached' => false]);
    }

    /**
     * Get active SSH hosts for the hybrid frontend
     */
    public function getHosts(Request $request)
    {
        $hosts = SshHost::where('active', true)
            ->select('id', 'name', 'hostname', 'user', 'port')
            ->orderBy('name')
            ->get();

        return response()->json($hosts);
    }
}
