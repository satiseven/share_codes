<?php

namespace App\Helpers\SupportedSite;

use App\Enums\Currency\CurrencyEnum;
use Symfony\Component\DomCrawler\Crawler;

abstract class _BaseSupportedSite
{
    protected $crawler;

    public function __construct( Crawler $crawler = NULL )
    {
        $this->crawler = $crawler;
    }

    public abstract function edited_link( string $link ): string;

    public abstract function get_product_image_src( array $selectors ): string;

    public abstract function get_product_title( array $selectors ): string;

    public abstract function get_product_price( array $selectors ): string;

    public abstract function get_product_currency( array $selectors ): string;

    protected function search_text( $selectors ): string
    {
        foreach ($selectors as $selector) {
            $result = $this->crawler->filter($selector)->first();
            if ( $result->getNode(0) && $result->text() ) {
                return $result->text();
            }
        }
        return '';
    }

    protected function search_inner_text( $selectors ): string
    {
        foreach ($selectors as $selector) {
            $result = $this->crawler->filter($selector)->first();
            if ( $result->getNode(0) && $result->innerText() ) {
                return $result->innerText();
            }
        }
        return '';
    }

    protected function search_src( $selectors ): string
    {
        foreach ($selectors as $selector) {
            $result = $this->crawler->filter($selector);

            if ( $result->getNode(0) ) {
                //dd($result->image());
                return $result->attr('src');
            }
        }
        return '';
    }

    protected function extract_price( string $string ): string
    {
        return preg_replace("/[^0-9.,!?]/", '', $string);
    }

    protected function extract_currency( string $string ): string
    {
        return preg_replace("/[^A-Z\p{Sc}!?]/", '', $string);
    }

    protected function convert_exchange_currency( string $symbol )
    {
        $TR  = [ 'TL', 'TRY', '₺' ];
        $USD = [ 'USD', '$' ];
        $AED = [ 'AED', 'د.إ', 'د.إ.' ];

        switch ( $symbol ) {
            case in_array($symbol, $TR):
                return CurrencyEnum::TRY;
            case in_array($symbol, $USD):
                return CurrencyEnum::USD;
            case in_array($symbol, $AED):
                return CurrencyEnum::AED;
            default :
                return '';
        }
    }
}
