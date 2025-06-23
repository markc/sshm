<?php

namespace App\Http\Controllers;

use App\Events\SshOutputReceived;
use App\Jobs\RunSshCommand;
use App\Models\SshHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class SshExecutionController extends Controller
{
    /**
     * Start a new SSH command execution process
     */
    public function start(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'host_id' => 'required|exists:ssh_hosts,id',
        ]);

        $processId = (string) Str::uuid();
        $command = $request->input('command');
        $hostId = $request->input('host_id');
        $userId = auth()->id();

        // Get the SSH host
        $host = SshHost::findOrFail($hostId);

        // Store authorization info for channel access
        Cache::put("process:{$processId}:user", $userId, now()->addHours(2));
        Cache::put("process:{$processId}:host", $hostId, now()->addHours(2));

        // Dispatch the job to the queue for execution
        RunSshCommand::dispatch($command, $processId, $userId, $hostId);

        // Return the unique ID so the frontend knows which channel to listen to
        return response()->json([
            'process_id' => $processId,
            'host' => $host->name,
        ]);
    }

    /**
     * Stop a running SSH process
     */
    public function stop(Request $request)
    {
        $request->validate(['process_id' => 'required|uuid']);
        $processId = $request->input('process_id');

        // Ensure the user is authorized to stop this process
        if (Cache::get("process:{$processId}:user") != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pid = Cache::get("process:{$processId}:pid");

        if ($pid) {
            // Use the Process facade to kill the OS process by its PID
            try {
                Process::run("kill {$pid}");
                SshOutputReceived::dispatch($processId, 'status', 'Process terminated by user.');

                // Clean up cache keys
                Cache::forget("process:{$processId}:user");
                Cache::forget("process:{$processId}:host");
                Cache::forget("process:{$processId}:pid");

                return response()->json(['message' => 'Process terminated.']);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to terminate process: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'Process not found or already finished.'], 404);
    }

    /**
     * Get the status of a running process
     */
    public function status(Request $request)
    {
        $request->validate(['process_id' => 'required|uuid']);
        $processId = $request->input('process_id');

        // Ensure the user is authorized to check this process
        if (Cache::get("process:{$processId}:user") != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pid = Cache::get("process:{$processId}:pid");
        $hostId = Cache::get("process:{$processId}:host");

        $status = [
            'process_id' => $processId,
            'is_running' => $pid !== null,
            'host_id' => $hostId,
        ];

        if ($hostId) {
            $host = SshHost::find($hostId);
            $status['host_name'] = $host?->name;
        }

        return response()->json($status);
    }
}
