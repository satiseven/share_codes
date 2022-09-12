<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::query()->pluck(Setting::VALUE, Setting::KEY);
        return view('admin.setting', compact('settings'));
    }

    public function update()
    {
        SettingHelper::clear();

        $only = [
            SettingKeysEnum::APP_NAME,
            SettingKeysEnum::APP_DEBUG,
            SettingKeysEnum::APP_URL,
            SettingKeysEnum::APP_DOMAIN,
            SettingKeysEnum::APP_ADMIN_SUBDOMAIN,
            SettingKeysEnum::MAIL_FROM_ADDRESS,
            SettingKeysEnum::ORDER_MAIL_ADDRESS,
            SettingKeysEnum::ORDERS_SLACK_URL,
            SettingKeysEnum::COMMISSION_PERCENT,
            SettingKeysEnum::MIN_ORDER_USD_AMOUNT,
            SettingKeysEnum::RATE_AED,
            SettingKeysEnum::PAYMENT_EXPIRED_MINUTE,
            SettingKeysEnum::PAYMENT_SUBSCRIBER_PASSWORD,
            SettingKeysEnum::BINANCE_API_URL,
            SettingKeysEnum::BINANCE_SOCKET_URL,
            SettingKeysEnum::BINANCE_SUB_ACCOUNT_TAG,
            SettingKeysEnum::BINANCE_SUB_ACCOUNT_MAIN_TAG,
            SettingKeysEnum::BINANCE_API_KEY,
            SettingKeysEnum::BINANCE_API_SECRET,
        ];

        foreach (request()->only($only) as $key => $value) {
            Setting::query()->where(Setting::KEY, $key)->update([
                Setting::VALUE => Crypt::encrypt($value),
            ]);
        }

        return redirect()->back()->with('success', 'Application settings updated!');
    }
}
