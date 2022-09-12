<?php

namespace App\Helpers\Binance;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;

class MinAmountHelper
{
    public static function get( $min_usd, $currency ): string
    {
        $usd_rate = 0;
        switch ( $currency ) {
            case CurrencyEnum::USD:
                $usd_rate = 1;
                break;
            case CurrencyEnum::AED:
                $usd_rate = SettingHelper::app()->get(SettingKeysEnum::RATE_AED);
                break;
            case CurrencyEnum::TRY:
                $usd_rate = app(BinanceService::class)->getSymbolPriceTicker("USDT$currency")['price'];
                break;
        }

        return self::calculator($min_usd, $usd_rate);
    }

    protected static function calculator( $min_usd, $usd_rate ): string
    {
        return $min_usd * $usd_rate;
    }
}
