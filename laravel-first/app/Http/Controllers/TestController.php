<?php

namespace App\Http\Controllers;

use App\Enums\Setting\SettingKeysEnum;
use App\Events\CheckBalanceEvent;
use App\Helpers\Binance\BinanceService;
use App\Helpers\Petite\CryptHelper;
use App\Helpers\Setting\SettingHelper;
use App\Jobs\Payment\PaymentListenJob;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

class TestController extends Controller
{
    public function get( Request $request, BinanceService $binance_service )
    {
        $num          = 1023;
        $formattedNum = number_format($num, 2);
        dd($formattedNum);
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh');
        \Illuminate\Support\Facades\Artisan::call('db:seeder');
        dd();
        //$telegram = new Api('BOT TOKEN');
        $sub_account       = $binance_service->subAccount(SettingHelper::app()->get(SettingKeysEnum::BINANCE_SUB_ACCOUNT_TAG));
        $sub_account_id    = $sub_account['subaccountId'];
        $sub_account_email = $sub_account['email'];
        dd('greldi');
        $keyboard = [
            [ '7', '8', '9' ],
            [ '4', '5', '6' ],
            [ '1', '2', '3' ],
            [ '0' ],
        ];

        //$response = Telegram::sendMessage([
        //    'chat_id' => '-1001655922883',
        //    'text' => 'Hello World',
        //]);

        $response = Telegram::sendMessage([
            'chat_id' => '-1001655922883',
            'text'    => 'Sana da kolay gelsin :)',
        ]);

        $messageId = $response->getMessageId();
        dd($messageId);


        dd('son');
        dd(SettingHelper::app()->get(SettingKeysEnum::BINANCE_API_KEY));

        $order    = Order::query()->find(2);
        $response = Http::withOptions([ 'verify' => FALSE ])->get(route('payment_start', CryptHelper::encode_parameter($order[Order::ID])));
        return $response->body();
        $payment = Payment::query()->find(12);

        dispatch(new PaymentListenJob($payment));

        dd(config('database.redis.options.prefix'));


        // ListenKey i getirir.
        //$binanceWebSocketApi->setAPI($payment[Payment::SUB_ACCOUNT_API_KEY], $payment[Payment::SUB_ACCOUNT_API_SECRET]);
        //$listenKey = $binanceWebSocketApi->getListenKey();
        //dd($listenKey);

        //dd($binanceWebSocketApi->getSubAccountAssets('broker_82162254_421893051_brokersubuser@stabit.com', 'USDT'));


        event(new CheckBalanceEvent($payment));
        // WsConnection
        return view('deneme');
    }

    public function web_hook( Request $request )
    {
        $update = Telegram::commandsHandler(TRUE);

        //$response = Telegram::sendMessage([
        //    'chat_id' => '-1001655922883',
        //    'text'    => $update,
        //]);
        return response('$response', 200);
    }
}
