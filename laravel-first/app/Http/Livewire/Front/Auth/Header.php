<?php

namespace App\Http\Livewire\Front\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Header extends Component
{
    protected $listeners = [ 'auth_check' => '$refresh' ];

    public $is_home = FALSE;

    public function logout()
    {
        Auth::logout();
        Session::flush();
        //$this->emit('auth_check');
        $this->redirect(route('home'));
    }

    public function render()
    {
        return view('livewire.front.auth.header');
    }
}
