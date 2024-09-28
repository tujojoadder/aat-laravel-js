<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $chatData;
    public function __construct($chatData)
    {
       $this->chatData=$chatData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */



     public function broadcastAs()
     {
         return 'getChatMessage';
     }


     public function broadcastWith()
     {
         return ['chat'=>$this->chatData];
     }



    public function broadcastOn()
    {
        return new PrivateChannel('broadcast-message');
    }
}
