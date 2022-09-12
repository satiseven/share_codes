<?php

namespace App\Jobs\Payment;

use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Binance\BinanceWebSocketApi;
use App\Helpers\Setting\SettingHelper;
use App\Jobs\BaseJob;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;


class PaymentListenJob extends BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;
    protected $binance_web_socket_api;

    public function __construct( $payment )
    {
        $this->payment                = $payment;
        $this->binance_web_socket_api = new BinanceWebSocketApi;
    }

    public function handle()
    {
        $this->single_process(function () {

            $payment = $this->payment;

            dump('Payment kaydı getirildi.', $payment);

            // ListenKey i getirir.
            $this->binance_web_socket_api->setAPI($payment[Payment::SUB_ACCOUNT_API_KEY], $payment[Payment::SUB_ACCOUNT_API_SECRET]);
            $listenKey = $this->binance_web_socket_api->getListenKey();
            dump('deneme');
            //Redis::publish("check_balance.{$payment[Payment::ID]}", json_encode([
            Redis::publish('check_balance', json_encode([
                'payment'            => $payment,
                'listenKey'          => $listenKey,
                'base_url' => SettingHelper::app()->get(SettingKeysEnum::APP_URL),
                'binance_socket_url' => SettingHelper::app()->get(SettingKeysEnum::BINANCE_SOCKET_URL),
                'payment_expire_minute' => SettingHelper::app()->get(SettingKeysEnum::BINANCE_SOCKET_URL),
                'payment_subscriber_password' => SettingHelper::app()->get(SettingKeysEnum::BINANCE_SOCKET_URL),
            ]));

            dump('Akış sonlandı.');
        });
    }
}
