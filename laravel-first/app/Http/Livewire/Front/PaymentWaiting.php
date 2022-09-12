<?php

namespace App\Http\Livewire\Front;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Helpers\Petite\CryptHelper;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use function auth;
use function view;

class PaymentWaiting extends Component
{
    public $order;
    public $payment;
    public $payment_status_check;

    public function mount( $order_id )
    {
        $this->order = Order::query()->where(Order::USER_ID, auth()->id())->find(CryptHelper::decode_parameter($order_id));
        Gate::authorize('view', $this->order);
        $this->payment              = $this->order->payment;
        $this->payment_status_check = $this->payment[Payment::STATUS];
    }

    public function payment_status_check()
    {
        $this->payment_status_check = Payment::query()->where(Payment::ORDER_ID, $this->order[Order::ID])->value(Payment::STATUS);
        if ( $this->payment_status_check !== PaymentStatusEnum::WAITING ) {
            $this->redirect(route('payment_result', CryptHelper::encode_parameter($this->order[Order::ID])));
        }
    }

    public function payment_cancel()
    {
        Payment::query()->find($this->payment[Payment::ID])->update([ Payment::STATUS => PaymentStatusEnum::CANCEL ]);
        Order::query()->find($this->order[Order::ID])->update([ Order::STATUS => OrderStatusEnum::CANCELED ]);
        $this->redirect(route('payment_result', CryptHelper::encode_parameter($this->order[Order::ID])));
    }


    public function render()
    {
        return view('livewire.front.payment-waiting');
    }
}
