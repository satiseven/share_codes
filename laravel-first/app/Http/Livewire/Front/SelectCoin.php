<?php

namespace App\Http\Livewire\Front;

use App\Models\Coin;
use Livewire\Component;
use function view;

class SelectCoin extends Component
{
    public $cryptos;
    public $selected_crypto;

    public function mount( $cryptos )
    {
        $this->cryptos         = $cryptos;
        $this->selected_crypto = $this->cryptos->first()->value(Coin::COIN);
    }

    public function updatedSelectedCrypto( $value )
    {
        $crypto = $this->cryptos->where(Coin::COIN, $value)->first();
        $this->emit('new_networks_event', $crypto->networks);
        $this->emitUp('selected_crypto_event', $value);
    }

    public function render()
    {
        return view('livewire.front.select-coin');
    }
}
