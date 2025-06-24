<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ultra-Fast SSH Terminal Output Event
 *
 * Optimized for real-time terminal streaming with minimal overhead:
 * - Binary data support for maximum performance
 * - Private channels for security
 * - Minimal serialization for speed
 */
class SshTerminalOutput implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $sessionId;

    public string $data;

    public string $type; // 'stdout', 'stderr', 'status'

    public float $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(string $sessionId, string $data, string $type = 'stdout')
    {
        $this->sessionId = $sessionId;
        $this->data = $data;
        $this->type = $type;
        $this->timestamp = microtime(true);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("ssh-terminal.{$this->sessionId}"),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'data' => $this->data,
            'type' => $this->type,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'terminal.output';
    }
}
