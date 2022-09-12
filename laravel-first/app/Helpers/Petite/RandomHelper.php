<?php

namespace App\Helpers\Petite;

use Illuminate\Support\Str;

class RandomHelper
{
    public static function string( int $int = 8 ): string
    {
        return Str::ascii(Str::upper(Str::random($int)));
    }

    public static function numeric( int $int = 8 ): string
    {
        return rand(( int) ( 1 . str_repeat(0, $int - 1) ), ( int) ( str_repeat(9, $int) ));
    }
}
