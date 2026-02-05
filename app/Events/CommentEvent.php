<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $commentData;
    public function __construct($commentData)
    {
        $this->commentData = $commentData;
    }




    public function broadcastAs()
    {
        return 'getComment';
    }
    public function broadcastWith()
    {
        return ['comment' => $this->commentData];
    }



    

    public function broadcastOn()
    {
        return new PrivateChannel('broadcast-comment');
    }
}
