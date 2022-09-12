<?php

namespace App\Http\Livewire\Front\Auth;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;

class ForgotPassword extends Component
{
    public $email;
    public $message = 'Please check your email.';

    public function rules()
    {
        return [ User::EMAIL => [ 'required', 'email', 'max:50' ] ];
    }

    public function submit()
    {
        $this->validate();

        if ( User::query()->where(User::EMAIL, $this->email)->exists() ) {
            $token = Str::random(64);

            DB::table('password_resets')->insert([
                'email'      => $this->email,
                'token'      => $token,
                'created_at' => now(),
            ]);

            Mail::to($this->email)->send(new PasswordResetMail($token));
        }

        //$this->message = trans('');
        $asset = asset('storage/front/img/ok_icon.svg');
        $this->message = "<img src=\"$asset\"/> Send mail successful.";
    }

    public function render()
    {
        return view('livewire.front.auth.forgot-password');
    }
}
