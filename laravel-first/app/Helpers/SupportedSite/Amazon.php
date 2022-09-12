<?php

namespace App\Helpers\SupportedSite;

use App\Helpers\Petite\MoneyHelper;

class Amazon extends _BaseSupportedSite
{
    public function edited_link( string $link ): string
    {
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
        return MoneyHelper::convert_float($this->extract_price($string));
    }

    public function get_product_currency( array $selectors ): string
    {
        $string = $this->search_text($selectors);
        $currency = $this->extract_currency($string);
        return $this->convert_exchange_currency($currency);
    }
}
