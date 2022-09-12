<?php

namespace App\Http\Livewire\Front;

use App\Helpers\Petite\CryptHelper;
use App\Models\Order;
use Livewire\Component;
use function route;
use function view;

class ProductCard extends Component
{
    public $order;
    public $image;
    public $title;
    public $price;
    public $currency;

    public $buy_button = TRUE;

    protected $listeners = [ 'view_product' ];

    public function mount( $order = NULL )
    {
        if ( $order ) {
            $this->order    = $order;
            $product        = $order['product'];
            $this->image    = $product['image'];
            $this->title    = $product['title'];
            $this->price    = $product['price'];
            $this->currency = $product['currency'];
        }
    }

    public function view_product( $order )
    {
        if ( ! $this->order ) {
            $this->order    = $order;
            $product        = $order['product'];
            $this->image    = $product['image'];
            $this->title    = $product['title'];
            $this->price    = $product['price'];
            $this->currency = $product['currency'];
        }
    }

    public function buy_product_click()
    {
        $this->redirect(route('exchange_to_crypto', CryptHelper::encode_parameter($this->order[Order::ID])));
    }

    public function render()
    {
        return view('livewire.front.product-card');
    }
}
