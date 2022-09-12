<?php

namespace App\Helpers\Petite;

class MoneyHelper
{
    public static function format( $money ): string
    {
        return number_format($money, 2);
    }

    public static function convert_float( string $money )
    {
        $money = str_replace(",",".",$money);
        $money = preg_replace('/\.(?=.*\.)/', '', $money);
        return floatval($money) ;
    }
}
