<?php

namespace App\Helpers\SupportedSite;

class Noon extends _BaseSupportedSite
{
    public function edited_link( string $link ): string
    {
        $path = parse_url($link, PHP_URL_PATH);
        if ( str_contains($path, 'uae-ar/') ||  str_contains($path, 'saudi-ar/') ||  str_contains($path, 'egypt-ar/') )
            $link = str_replace('-ar/', '-en/', $link);

        return $link;
    }

    public function get_product_image_src( array $selectors ): string
    {
        return $this->search_src($selectors);
    }

    public function get_product_title( array $selectors ): string
    {
        return $this->search_text($selectors);
    }

    public function get_product_price( array $selectors ): string
    {
        $string = $this->search_text($selectors);
        return $this->extract_price($string);
    }

    public function get_product_currency( array $selectors ): string
    {
        $string   = $this->search_inner_text($selectors);
        $currency = $this->extract_currency($string);
        return $this->convert_exchange_currency($currency);
    }
}
