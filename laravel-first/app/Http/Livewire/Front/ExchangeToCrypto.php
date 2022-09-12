<?php

namespace App\Http\Livewire\Front;

use App\Helpers\Binance\CalculatorHelper;
use App\Helpers\Petite\CryptHelper;
use App\Models\Coin;
use App\Models\Order;
use Livewire\Component;
use function auth;
use function request;
use function route;
use function view;

class ExchangeToCrypto extends Component
{
    public $cryptos;
    public $order;
    public $selected_crypto;
    public $selected_network;
    public $selected_crypto_product_price;

    protected $listeners = [ 'auth_check' => '$refresh', 'selected_crypto_event', 'selected_network_event' ];

    public function mount( $order_id )
    {
        $this->order   = request('order');
        $this->cryptos = Coin::query()->activeNetworks()->where(Coin::IS_ACTIVE, TRUE)->get([ Coin::ID, Coin::NAME, Coin::COIN ]);
    }

    public function selected_crypto_event( $selected_crypto )
    {
        $this->selected_crypto               = $selected_crypto;
        $this->selected_crypto_product_price = CalculatorHelper::convert_crypto($this->order, $selected_crypto);
    }

    public function selected_network_event( $selected_network )
    {
        $this->selected_network = $selected_network;
    }

    public function selected_crypto_continue()
    {
        if ( auth()->check() ) {
            $this->order->update([
                Order::USER_ID => auth()->id(),
                Order::CRYPTO  => $this->selected_crypto,
                Order::NETWORK => $this->selected_network,
            ]);

            $this->redirect(route('personal_info', CryptHelper::encode_parameter($this->order[Order::ID])));
        }
    }


    public function render()
    {
        return view('livewire.front.exchange-to-crypto');
    }
}
