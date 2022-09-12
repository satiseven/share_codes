<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckBalanceEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $connection = 'redis';

    public $queue = 'channels';

    protected $payment;

    public function __construct( $payment )
    {
        $this->payment = $payment;
    }

    public function broadcastOn()
    {
        return new Channel('check-balance');
    }


    public function broadcastWith()
    {
        return [
            'actionId'   => 'deneme 1',
            'actionData' => 'deneme 2',
        ];
    }
}
