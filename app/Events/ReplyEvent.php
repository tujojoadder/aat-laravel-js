<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReplyEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
   
    private $replyData;
    public function __construct($replyData)
    {
        $this->replyData=$replyData;
    }

    


    public function broadcastAs()
    {
        return 'getReply';
    }
    public function broadcastWith()
    {
        return ['reply' => $this->replyData];
    }




    public function broadcastOn()
    {
        return new PrivateChannel('broadcast-reply');
    }
}
