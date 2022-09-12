<?php

namespace App\Http\Livewire\Front\Auth;

use App\Enums\Auth\GuardEnum;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $email;
    public $password;
    public $remember;

    public function rules()
    {
        return [
            User::EMAIL    => [ 'required', 'email', 'max:50' ],
            User::PASSWORD => [ 'required', 'string', 'max:191' ],
        ];
    }

    public function updated( $propertyName )
    {
        $this->validateOnly($propertyName);
    }

    public function submit()
    {
        $this->validate();

        $credentials = [ User::EMAIL => $this->email, User::PASSWORD => $this->password ];
        if ( ! Auth::guard(GuardEnum::USER)->validate($credentials) ) {
            $this->addError('login_error', trans('auth.failed'));
            return;
        }

        $user = Auth::guard(GuardEnum::USER)->getProvider()->retrieveByCredentials($credentials);

        Auth::guard(GuardEnum::USER)->login($user, $this->remember);

        $this->dispatchBrowserEvent('success_login');
        $this->emit('auth_check');
    }

    public function render()
    {
        return view('livewire.front.auth.login');
    }
}
