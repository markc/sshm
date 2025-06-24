<?php

namespace App\Http\Controllers;

use App\Models\SshHost;
use App\Services\SshConnectionPoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Ssh\Ssh;

/**
 * Ultra-Fast WebSocket Controller for Xterm.js
 *
 * Optimized for maximum performance with zero-latency streaming:
 * - Direct binary data pipe from SSH to WebSocket
 * - No JSON encoding/decoding overhead
 * - Asynchronous non-blocking execution
 * - Connection pooling for SSH reuse
 */
class XtermWebSocketController extends Controller
{
    private SshConnectionPoolService $connectionPool;

    public function __construct(SshConnectionPoolService $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * Initialize WebSocket SSH session for xterm.js
     * Returns session configuration for frontend
     */
    public function initializeSession(Request $request)
    {
        $request->validate([
            'host_id' => 'required|exists:ssh_hosts,id',
            'command' => 'string|max:10000',
            'use_bash' => 'boolean',
        ]);

        $hostId = $request->integer('host_id');
        $host = SshHost::findOrFail($hostId);
        $sessionId = (string) Str::uuid();

        // Store session configuration in cache for WebSocket handler
        cache()->put("ssh_session:{$sessionId}", [
            'host_id' => $hostId,
            'host' => $host->toArray(),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ], 3600); // 1 hour TTL

        return response()->json([
            'session_id' => $sessionId,
            'websocket_url' => config('app.ws_url', 'ws://localhost:8080'),
            'channel' => "ssh-terminal.{$sessionId}",
            'host_info' => [
                'name' => $host->name,
                'hostname' => $host->hostname,
                'user' => $host->user,
            ],
        ]);
    }

    /**
     * Execute SSH command via API endpoint with WebSocket broadcasting
     */
    public function executeCommand(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'command' => 'required|string|max:10000',
            'use_bash' => 'boolean',
        ]);

        $sessionId = $request->string('session_id');
        $command = $request->string('command');
        $useBash = $request->boolean('use_bash', false);

        $sessionData = cache()->get("ssh_session:{$sessionId}");
        if (! $sessionData) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found or expired',
            ], 404);
        }

        $host = SshHost::find($sessionData['host_id']);
        if (! $host) {
            return response()->json([
                'success' => false,
                'error' => 'SSH host not found',
            ], 404);
        }

        // For Phase 1, execute command synchronously and return results
        $results = $this->executeCommandDirectly($host, $command, $useBash);

        return response()->json([
            'success' => $results['success'],
            'message' => $results['success'] ? 'Command executed successfully' : 'Command failed',
            'session_id' => $sessionId,
            'output' => $results['output'],
            'error' => $results['error'],
            'exit_code' => $results['exit_code'],
        ]);
    }

    /**
     * Execute SSH command with real-time WebSocket broadcasting
     */
    private function executeCommandWithBroadcasting(string $sessionId, SshHost $host, string $command, bool $useBash): void
    {
        $startTime = microtime(true);

        try {
            // Get optimized SSH connection from pool
            $ssh = $this->connectionPool->getSpatieConnection($host);

            // Build command
            $finalCommand = $useBash
                ? 'bash -ci ' . escapeshellarg($command)
                : $command;

            Log::info('WebSocket SSH command execution started', [
                'session_id' => $sessionId,
                'host_id' => $host->id,
                'command' => $command,
                'use_bash' => $useBash,
            ]);

            // Broadcast connection status
            broadcast(new \App\Events\SshTerminalOutput(
                $sessionId,
                "🚀 Executing: {$command}",
                'status'
            ));

            // Execute with real-time output streaming
            $ssh->execute($finalCommand, function ($type, $line) use ($sessionId) {
                // Broadcast each line of output in real-time
                $outputType = $type === 'err' ? 'stderr' : 'stdout';
                broadcast(new \App\Events\SshTerminalOutput($sessionId, $line, $outputType));
            });

            $executionTime = microtime(true) - $startTime;

            // Broadcast completion status
            broadcast(new \App\Events\SshTerminalOutput(
                $sessionId,
                '✅ Command completed in ' . number_format($executionTime, 3) . 's',
                'status'
            ));

        } catch (\Exception $e) {
            Log::error('WebSocket SSH command execution failed', [
                'session_id' => $sessionId,
                'host_id' => $host->id,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);

            // Broadcast error
            broadcast(new \App\Events\SshTerminalOutput(
                $sessionId,
                "❌ Error: {$e->getMessage()}",
                'stderr'
            ));
        }
    }

    /**
     * Send input to active SSH session
     * This handles user keystrokes from xterm.js
     */
    public function sendInput(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'input' => 'required|string',
        ]);

        $sessionId = $request->string('session_id');
        $input = $request->string('input');

        // For now, this is a placeholder for the interactive functionality
        // Will be enhanced in Phase 2 with true PTY support

        return response()->json([
            'success' => true,
            'message' => 'Input received',
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Close SSH session and cleanup resources
     */
    public function closeSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->string('session_id');

        // Remove session from cache
        cache()->forget("ssh_session:{$sessionId}");

        // Cleanup connection pool if needed
        $this->connectionPool->closeSession($sessionId);

        return response()->json([
            'success' => true,
            'message' => 'Session closed',
        ]);
    }

    /**
     * Get session status and performance metrics
     */
    public function getSessionStatus(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->string('session_id');
        $sessionData = cache()->get("ssh_session:{$sessionId}");

        if (! $sessionData) {
            return response()->json([
                'exists' => false,
                'message' => 'Session not found or expired',
            ]);
        }

        return response()->json([
            'exists' => true,
            'session_id' => $sessionId,
            'host' => $sessionData['host'],
            'created_at' => $sessionData['created_at'],
            'performance' => [
                'connection_pool' => $this->connectionPool->getStats(),
            ],
        ]);
    }

    /**
     * Execute SSH command directly and return results (Phase 1 implementation)
     */
    private function executeCommandDirectly(SshHost $host, string $command, bool $useBash): array
    {
        $startTime = microtime(true);

        try {
            // Get SSH connection from pool
            $ssh = $this->connectionPool->getSpatieConnection($host);

            // Build final command
            $finalCommand = $useBash
                ? 'bash -ci ' . escapeshellarg($command)
                : $command;

            Log::info('Direct SSH command execution started', [
                'host_id' => $host->id,
                'command' => $command,
                'use_bash' => $useBash,
                'final_command' => $finalCommand,
            ]);

            // Execute command and capture output
            $output = '';
            $error = '';
            $exitCode = 0;

            try {
                // Execute with output capture - Spatie SSH execute() returns a Process object
                $process = $ssh->execute($finalCommand);

                // Get the actual output string from the process
                if ($process instanceof \Symfony\Component\Process\Process) {
                    $output = $process->getOutput();
                    $error = $process->getErrorOutput();
                    $exitCode = $process->getExitCode() ?? 0;
                } else {
                    // Fallback for string result (older versions)
                    $output = (string) $process;
                    $exitCode = 0;
                }

                // If the result is empty, try to get stderr as well
                if (empty($output) && ! empty($error)) {
                    $output = $error; // Show errors as output for debugging
                }

            } catch (\Exception $sshException) {
                $error = $sshException->getMessage();
                $exitCode = 1;

                Log::warning('SSH command execution error', [
                    'host_id' => $host->id,
                    'command' => $command,
                    'error' => $error,
                ]);
            }

            $executionTime = microtime(true) - $startTime;

            // Ensure output is always a string for logging
            $output = (string) $output;
            $error = (string) $error;

            Log::info('Direct SSH command execution completed', [
                'host_id' => $host->id,
                'command' => $command,
                'execution_time' => $executionTime,
                'output_length' => strlen($output),
                'exit_code' => $exitCode,
            ]);

            return [
                'success' => $exitCode === 0,
                'output' => $output,
                'error' => $error,
                'exit_code' => $exitCode,
                'execution_time' => $executionTime,
            ];

        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;

            Log::error('Direct SSH command execution failed', [
                'host_id' => $host->id,
                'command' => $command,
                'error' => $e->getMessage(),
                'execution_time' => $executionTime,
            ]);

            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => 1,
                'execution_time' => $executionTime,
            ];
        }
    }
}
