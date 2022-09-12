<?php

namespace App\Http\Livewire\Front;

use App\Enums\Payment\PaymentStatusEnum;
use App\Helpers\Petite\CryptHelper;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PaymentResult extends Component
{
    public $order;
    public $payment;

    public function mount( $order_id )
    {
        $this->order = Order::query()->where(Order::USER_ID, auth()->id())->find(CryptHelper::decode_parameter($order_id));
        Gate::authorize('view', $this->order);
        $this->payment = $this->order->payment;
    }

    public function render()
    {
        switch ( $this->payment[Payment::STATUS] ) {
            case PaymentStatusEnum::RECEIPT:
                return view('livewire.front.payment_result.receipt');
            case PaymentStatusEnum::EXPIRED:
                return view('livewire.front.payment_result.expired');
            case PaymentStatusEnum::CANCEL:
                return view('livewire.front.payment_result.cancel');
        }
    }
}
