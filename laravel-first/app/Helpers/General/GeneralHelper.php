<?php

namespace App\Helpers\General;

class GeneralHelper
{
    public static function get_mac_address(): string
    {
        return strtok(exec('getmac'), ' ');
    }
}
