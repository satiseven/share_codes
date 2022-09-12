<?php

namespace App\Helpers\Setting;

use App\Enums\Cache\Settings\SettingsCacheEnum;
use App\Enums\Setting\SettingKeysEnum;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingHelper
{
    protected $settings;

    public function __construct()
    {
        $this->init();
    }

    public static function app()
    {
        return app(SettingHelper::class);
    }

    public function get( string $key, $only_value = TRUE )
    {
        $setting = $this->settings->where(Setting::KEY, $key)->first();
        //if ( ! $setting ) $this->init();
        if ( $only_value ) {
            //return $setting[Setting::VALUE];
        }

        return $setting;
    }

    public static function clear(): void
    {
        Cache::delete(SettingsCacheEnum::KEY);
    }

    protected function init()
    {
        if ( Cache::has(SettingsCacheEnum::KEY) ) {
            $this->settings = Crypt::decrypt(Cache::get(SettingsCacheEnum::KEY));
        } else {
            $this->settings = Setting::query()->get()->keyBy(Setting::KEY);
            Cache::put(SettingsCacheEnum::KEY, Crypt::encrypt($this->settings), SettingsCacheEnum::TTL);
        }

        //\Config::set([
        //    'app.name'          => $this->settings[SettingKeysEnum::APP_NAME],
        //    'app.debug'         => $this->settings[SettingKeysEnum::APP_DEBUG],
        //    'app.url'           => $this->settings[SettingKeysEnum::APP_URL],
        //    'mail.from.address' => $this->settings[SettingKeysEnum::MAIL_FROM_ADDRESS],
        //    'mail.from.name'    => $this->settings[SettingKeysEnum::APP_NAME],
        //]);
    }
}
