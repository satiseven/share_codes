<?php

namespace App\Helpers\Binance;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use App\Models\Coin;
use App\Models\Order;

class CalculatorHelper
{
    public static function convert_crypto( $order, string $crypto ): string
    {
        $binance_service = app(BinanceService::class);

        $product_currency = $order[Order::PRODUCT]['currency'];

        $precision = Coin::query()->where([
            [ Coin::COIN, $crypto ],
            [ Coin::IS_ACTIVE, TRUE ],
        ])->value(Coin::PRECISION);

        $percent    = SettingHelper::app()->get(SettingKeysEnum::COMMISSION_PERCENT);
        $commission = $percent / 100;

        $order_price = $order['product']['price'];

        switch ( $product_currency ) {
            case CurrencyEnum::USD:
                $crypto_product_price = CalculatorHelper::crypto_basis_process($crypto, $commission, $order_price, $binance_service);
                break;
            case CurrencyEnum::AED:
                $usdt_value           = SettingHelper::app()->get(SettingKeysEnum::RATE_AED);
                $order_price          = $order_price / $usdt_value;
                $crypto_product_price = CalculatorHelper::crypto_basis_process($crypto, $commission, $order_price, $binance_service);
                break;
            default:
                $crypto_currency = $binance_service->getSymbolPriceTicker("$crypto$product_currency")['price']; // Böyle bir işlem çifti varsa fiyatı al.

                if ( $crypto_currency ) { // Böyle bir işlem çifti varsa ürün fiyatını kripto para cinsinden hesapla.
                    $crypto_product_price = CalculatorHelper::crypto_basis_process($crypto, $commission, $order_price, $binance_service, $crypto_currency);
                } else { // Böyle bir işlem çifti yoksa ürün fiyatını önce USDT'ye çevir. Daha sonra [başka bir kripto para][USDT] çifti ile istenen kripto paranın miktarını hesapla.
                    $usdt_value = $binance_service->getSymbolPriceTicker("USDT$product_currency")['price'];
                    throw_if(! $usdt_value); // Eğer para birimi USDT olarak bile deskteklenmiyorsa.

                    $crypto_product_price = CalculatorHelper::crypto_basis_process($crypto, $commission, $order_price, $binance_service, $usdt_value);
                }
                break;
        }

        return self::rounder($crypto_product_price, $precision); // Gelen kripto para cinsinden fiyatı yukarı yuvarla.
    }

    private static function rounder( $crypto_product_price, int $precision )
    {
        if ( $crypto_product_price > round($crypto_product_price, $precision, PHP_ROUND_HALF_UP) ) {
            if ( $precision > 0 ) {
                $step                 = str_repeat('0', $precision - 1);
                $number               = "0.{$step}1";
                $crypto_product_price += doubleval($number);
            } else {
                $crypto_product_price++;
            }
        }
        return round($crypto_product_price, $precision, PHP_ROUND_HALF_UP);
    }

    private static function crypto_basis_process( $crypto, $commission, $order_price, $binance_service, $rate = NULL )
    {
        if ( $crypto !== 'USDT' ) {
            if ( $rate ) {
                $crypto_currency_with_commission = $rate - ( $rate * $commission );
            } else {
                $crypto_value = $binance_service->getSymbolPriceTicker("{$crypto}USDT")['price'];
                throw_if(! $crypto_value); // Eğer para birimi USDT deskteklenmiyorsa.
                $crypto_currency_with_commission = $crypto_value - ( $crypto_value * $commission );
            }

            return $order_price / $crypto_currency_with_commission;
        } else {
            return CalculatorHelper::usdt_basis_process($commission, $order_price, $rate ?? 1);
        }
    }

    private static function usdt_basis_process( $commission, $order_price, $rate = 1 )
    {
        $crypto_currency_with_commission = $rate - ( $rate * $commission );
        
        return $order_price / $crypto_currency_with_commission;
    }

}
