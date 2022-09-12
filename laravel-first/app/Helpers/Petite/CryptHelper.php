<?php

namespace App\Helpers\Petite;

use Illuminate\Support\Facades\Crypt;

class CryptHelper
{
    public static function encode_parameter( $data )
    {
        return Crypt::encrypt($data);
    }

    public static function decode_parameter( $crypt_data )
    {
        return Crypt::decrypt($crypt_data);
    }
}
