<?php

namespace App\Http\Requests\Front\Auth;

use App\Http\Requests\BaseRequest;
use App\Models\User;
use App\Rules\MatchOldPassword;

class ChangePasswordRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'old_password'                   => [ 'required', 'string', new MatchOldPassword ],
            User::PASSWORD                   => [
                'required', 'string', 'min:8', 'max:100',
                //    'regex:/[a-z]/',      // must contain at least one lowercase letter
                //    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                //    'regex:/[0-9]/',      // must contain at least one digit
                //    'regex:/[@$!%.*#?&]/', // must contain a special character
                'confirmed',
            ],
            User::PASSWORD . '_confirmation' => [
                'required', 'string',
            ],
        ];
    }
}
