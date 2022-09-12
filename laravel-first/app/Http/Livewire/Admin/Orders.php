<?php

namespace App\Http\Livewire\Admin;

use App\Enums\Order\OrderAddressEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $order_status_search = [];
    public $payment_status_search = [ PaymentStatusEnum::RECEIPT ];
    public $text_search;

    public function render()
    {

        return view('livewire.admin.orders', [
            'orders' => Order::query()
                ->when($this->text_search, function ( Builder $query ) {
                    $text_search = $this->text_search;
                    $columns     = [
                        Order::NUMBER,
                        Order::LINK,
                        Order::CRYPTO,
                        Order::INVOICE_ADDRESS . '->' . OrderAddressEnum::FIRST_NAME,
                        Order::INVOICE_ADDRESS . '->' . OrderAddressEnum::LAST_NAME,
                        Order::INVOICE_ADDRESS . '->' . OrderAddressEnum::COUNTRY,
                        Order::INVOICE_ADDRESS . '->' . OrderAddressEnum::COMPANY_NAME,
                        Order::INVOICE_ADDRESS . '->' . OrderAddressEnum::ZIP_CODE,
                        Order::SHIPPING_ADDRESS . '->' . OrderAddressEnum::FIRST_NAME,
                        Order::SHIPPING_ADDRESS . '->' . OrderAddressEnum::LAST_NAME,
                        Order::SHIPPING_ADDRESS . '->' . OrderAddressEnum::COUNTRY,
                        Order::SHIPPING_ADDRESS . '->' . OrderAddressEnum::COMPANY_NAME,
                        Order::SHIPPING_ADDRESS . '->' . OrderAddressEnum::ZIP_CODE,
                        Order::PHONE,
                        Order::PRODUCT . '->title',
                        Order::PRODUCT . '->currency',
                    ];

                    foreach ($columns as $column) {
                        $query = $query->orWhere($column, 'like', '%' . $text_search . '%');
                    }

                    return $query;
                })
                ->when($this->payment_status_search, function ( $query ) {
                    return $query->whereHas('payment', function ( $query ) {
                        return $query->whereIn(Payment::STATUS, $this->payment_status_search);
                    });
                })
                ->when($this->order_status_search, function ( $query ) {
                    return $query->whereIn(Order::STATUS, $this->order_status_search);
                })->orderByDesc(Order::ID)->paginate(5),
        ]);
    }
}
