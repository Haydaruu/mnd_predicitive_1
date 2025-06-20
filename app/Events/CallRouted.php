<?php

namespace App\Events;

use App\Models\Agent;
use App\Models\Nasbah;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRouted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

    public $agent;
    public $nasabah;


    public function __construct(Agent $agent, Nasbah $nasabah)
    {
        $this->agent = $agent;
        $this->nasabah = $nasabah;
    }

    
    public function broadcastOn()
    {
        return new PrivateChannel('agent.' .$this->agent->id);
        
    }

     public function broadcastAs()
    {
        return 'call.routed';
    }
}
