<?php

namespace App\Providers;

use App\Helpers\Binance\BinanceApi;
use App\Helpers\Binance\BinanceService;
use App\Helpers\Binance\BinanceWebSocketApi;
use App\Helpers\Setting\SettingHelper;
use Illuminate\Support\ServiceProvider;

class ServiceContainerProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SettingHelper::class, function () {
            return new SettingHelper();
        });

        $this->app->singleton(BinanceService::class, static function ( $app ) {
            return new BinanceService($app->make(BinanceApi::class));
        });

        $this->app->singleton(BinanceWebSocketApi::class, static function ( $app ) {
            return new BinanceWebSocketApi($app->make(BinanceApi::class));
        });
    }

    public function boot()
    {
        //
    }
}
