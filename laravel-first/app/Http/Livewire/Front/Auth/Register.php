<?php

namespace App\Http\Livewire\Front\Auth;

use App\Events\Front\Auth\UserRegisterEvent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public $name;
    public $surname;
    public $email;
    public $password;
    public $password_confirmation;
    public $remember;

    public function rules()
    {
        return [
            User::NAME                       => [ 'required', 'string', 'max:50' ],
            User::SURNAME                    => [ 'required', 'string', 'max:50' ],
            User::EMAIL                      => [ 'required', 'email', 'max:50', 'unique:users' ],
            User::PASSWORD                   => [ Password::required(), 'string', Password::min(8)->letters()->mixedCase()->symbols(), 'max:100' ],
            User::PASSWORD . '_confirmation' => [ Password::required(), 'string', 'max:100', 'same:' . User::PASSWORD ],
        ];
    }

    public function updated( $propertyName )
    {
        $this->validateOnly($propertyName);
    }

    public function submit()
    {
        $this->validate();

        $user[User::NAME]     = $this->name;
        $user[User::SURNAME]  = $this->surname;
        $user[User::EMAIL]    = $this->email;
        $user[User::PASSWORD] = Hash::make($this->password);
        $user                 = User::query()->create($user);

        event(new UserRegisterEvent($user));

        auth()->login($user);

        $this->dispatchBrowserEvent('success_register');
        $this->emit('auth_check');
    }

    public function render()
    {
        return view('livewire.front.auth.register');
    }
}
