<?php

namespace App\Http\Livewire\Admin;

use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use Livewire\Component;

class OrderDetail extends Component
{
    public $order;
    public $payment;
    public $order_status;

    public $order_statuses = [
        OrderStatusEnum::SEARCH,
        OrderStatusEnum::ORDERED,
        OrderStatusEnum::CANCELED,
        OrderStatusEnum::ACCEPTED,
        OrderStatusEnum::PURCHASED,
        OrderStatusEnum::SHIPMENT,
        OrderStatusEnum::DELIVERED,
    ];

    protected $listeners = [ 'order_detail_event' ];

    public function updatedOrderStatus( $status )
    {
        $order_id = $this->order[Order::ID];
        Order::query()->findOrFail($order_id)->update([
            Order::STATUS => $status,
        ]);
        $this->emit('order_status_changed', $order_id, $status);
    }

    public function order_detail_event( $order )
    {
        $this->order        = $order;
        $this->payment      = $order['payment'];
        $this->order_status = $this->order[Order::STATUS];
    }

    public function render()
    {
        return view('livewire.admin.order-detail');
    }
}
