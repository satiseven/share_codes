<?php

namespace App\Http\Requests\Front\Livewire;

use App\Http\Requests\BaseRequest;

class TakeProductUrlRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'url' => [ 'required' ],
        ];
    }
}
