<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SSH Terminal Input Event
 * 
 * Handles user input from xterm.js to SSH session:
 * - Keystroke data from frontend
 * - Control characters (Ctrl+C, etc.)
 * - Optimized for minimal latency
 */
class SshTerminalInput implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $sessionId;
    public string $input;
    public float $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(string $sessionId, string $input)
    {
        $this->sessionId = $sessionId;
        $this->input = $input;
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
            'input' => $this->input,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'terminal.input';
    }
}