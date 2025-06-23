<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SshOutputReceived implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $processId  The unique process identifier
     * @param  string  $type  The type of output: 'out', 'err', or 'status'
     * @param  string  $line  The output line content
     */
    public function __construct(
        public string $processId,
        public string $type,
        public string $line
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast on the private channel matching the process ID
        return [new PrivateChannel('ssh-process.' . $this->processId)];
    }
}
