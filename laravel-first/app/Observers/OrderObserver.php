<?php

namespace App\Observers;

use App\Events\OrderStatusChangedEvent;
use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     *
     * @param \App\Models\Order $order
     *
     * @return void
     */
    public function created( Order $order )
    {
        //
    }

    /**
     * Handle the Order "updated" event.
     *
     * @param \App\Models\Order $order
     *
     * @return void
     */
    public function updating( Order $order )
    {
        $old_status = $order->getOriginal(Order::STATUS);
        $new_status = $order[Order::STATUS];

        if ( $order[Order::USER_ID] && $old_status !== $new_status ) {
            event(new OrderStatusChangedEvent($order));
        }
    }

    /**
     * Handle the Order "updated" event.
     *
     * @param \App\Models\Order $order
     *
     * @return void
     */
    public function updated( Order $order )
    {
        //
    }

    /**
     * Handle the Order "deleted" event.
     *
     * @param \App\Models\Order $order
     *
     * @return void
     */
    public function deleted( Order $order )
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     *
     * @param \App\Models\Order $order
     *
     * @return void
     */
    public function restored( Order $order )
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     *
     * @param \App\Models\Order $order
     *
     * @return void
     */
    public function forceDeleted( Order $order )
    {
        //
    }
}
