<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LumbarongEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $eventName;
    public array $data;
    public ?string $channelName;

    /**
     * Create a new event instance.
     */
    public function __construct(string $eventName, array $data, ?string $channelName = null)
    {
        $this->eventName = $eventName;
        $this->data = $data;
        $this->channelName = $channelName;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $name = $this->channelName ?: 'public';
        return [new Channel($name)];
    }

    /**
     * Get the event name to broadcast as.
     */
    public function broadcastAs(): string
    {
        return $this->eventName;
    }
}
