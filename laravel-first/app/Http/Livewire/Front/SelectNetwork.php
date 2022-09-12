<?php

namespace App\Http\Livewire\Front;

use App\Models\Network;
use Livewire\Component;
use function collect;
use function view;

class SelectNetwork extends Component
{
    public $networks = [];
    public $selected_network;

    protected $listeners = [ 'new_networks_event' ];

    public function new_networks_event( $networks )
    {
        $this->networks         = $networks;
        $this->selected_network = collect($networks)->first()[Network::NETWORK];
        $this->updatedSelectedNetwork($this->selected_network);
    }

    public function updatedSelectedNetwork( $value )
    {
        $this->emitUp('selected_network_event', $value);
    }

    public function render()
    {
        return view('livewire.front.select-network');
    }
}
