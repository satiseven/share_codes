<?php

namespace App\Providers;

use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        View::composer('*', function ( $view ) {
            $view->with('app_name', SettingHelper::app()->get(SettingKeysEnum::APP_NAME));
        });
    }
}
