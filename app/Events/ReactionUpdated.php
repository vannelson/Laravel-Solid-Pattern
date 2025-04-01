<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ReactionUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $reaction;

    /**
     * Create a new event instance.
     *
     * @param mixed $reaction
     */
    public function __construct($reaction)
    {
        $this->reaction = $reaction;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('song-reactions');
    }

    /**
     * Optionally rename the broadcast event.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'reaction.updated';
    }
}
