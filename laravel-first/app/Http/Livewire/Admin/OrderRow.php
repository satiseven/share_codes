<?php

namespace App\Http\Livewire\Admin;

use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use Livewire\Component;

class OrderRow extends Component
{
    protected $listeners = [ 'order_status_changed' ];

    public $order;
    public $payment;
    public $payment_status_color;
    public $order_status_color;

    public function mount( $order )
    {
        $this->order   = $order;
        $this->payment = $order->load('payment')->payment;
        $this->payment_status_color();
        $this->order_status_color();
    }

    public function order_status_changed( $order_id, $status )
    {
        if ( $this->order[Order::ID] === $order_id ) {
            $this->order[Order::STATUS] = $status;
            $this->order_status_color();
        }
    }

    public function order_detail_click()
    {
        $this->emit('order_detail_event', $this->order);
    }

    private function payment_status_color()
    {
        switch ( $this->payment[\App\Models\Payment::STATUS] ) {
            case \App\Enums\Payment\PaymentStatusEnum::EXPIRED:
                $this->payment_status_color = 'danger';
                break;
            case \App\Enums\Payment\PaymentStatusEnum::WAITING:
                $this->payment_status_color = 'warning';
                break;
            case \App\Enums\Payment\PaymentStatusEnum::RECEIPT:
                $this->payment_status_color = 'success';
                break;
        }
    }

    private function order_status_color()
    {
        switch ( $this->order[\App\Models\Order::STATUS] ) {
            case OrderStatusEnum::SEARCH :
                $this->order_status_color = 'indigo';
                break;
            case OrderStatusEnum::ORDERED :
                $this->order_status_color = 'primary';
                break;
            case OrderStatusEnum::CANCELED :
                $this->order_status_color = 'danger';
                break;
            case OrderStatusEnum::ACCEPTED :
                $this->order_status_color = 'success';
                break;
            case OrderStatusEnum::PURCHASED :
                $this->order_status_color = 'info';
                break;
            case OrderStatusEnum::SHIPMENT :
                $this->order_status_color = 'pink';
                break;
            case OrderStatusEnum::DELIVERED :
                $this->order_status_color = 'lime';
                break;
        }
    }

    public function render()
    {
        return view('livewire.admin.order-row');
    }
}
