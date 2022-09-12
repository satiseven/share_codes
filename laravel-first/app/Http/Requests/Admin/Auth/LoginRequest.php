<?php

namespace App\Http\Requests\Admin\Auth;

use App\Http\Requests\BaseRequest;
use App\Models\User;

class LoginRequest extends BaseRequest
{
    public function rules()
    {
        return [
            User::EMAIL    => [ 'required', 'email', 'max:50' ],
            User::PASSWORD => [ 'required', 'string', 'max:191' ],
        ];
    }
}

