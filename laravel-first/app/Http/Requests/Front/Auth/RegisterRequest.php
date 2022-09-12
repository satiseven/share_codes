<?php

namespace App\Http\Requests\Front\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules()
    {
        return [
            User::NAME                       => [ 'required', 'string', 'max:50' ],
            User::SURNAME                    => [ 'required', 'string', 'max:50' ],
            User::EMAIL                      => [ 'required', 'email', 'max:50' ],
            User::PASSWORD                   => [
                'required', 'string', 'min:8', 'max:100',
            //    'regex:/[a-z]/',      // must contain at least one lowercase letter
            //    'regex:/[A-Z]/',      // must contain at least one uppercase letter
            //    'regex:/[0-9]/',      // must contain at least one digit
            //    'regex:/[@$!%.*#?&]/', // must contain a special character
                'confirmed',
            ],
            User::PASSWORD . '_confirmation' => [
                'required', 'string'
            ],
        ];
    }
}

