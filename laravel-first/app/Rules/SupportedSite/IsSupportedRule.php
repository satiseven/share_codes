<?php

namespace App\Rules\SupportedSite;

use App\Models\SupportedSite;
use Illuminate\Contracts\Validation\Rule;

class IsSupportedRule implements Rule
{
    public function passes( $attribute, $value )
    {
        $base_url = parse_url($value, PHP_URL_HOST);
        return  SupportedSite::query()->where(SupportedSite::BASE_URL, $base_url)->exists();
    }

    public function message()
    {
        return 'Aradığınız ürünü satan siteyi desteklemiyoruz.';
    }
}
