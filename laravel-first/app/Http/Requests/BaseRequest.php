<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    protected $lang_file;

    public function authorize()
    {
        return TRUE;
    }

    protected function trans( string $field, array $replace = [] )
    {
        return trans("$this->lang_file.$field", $replace);
    }
}
