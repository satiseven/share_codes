<?php

namespace App\Console\Commands;

use App\Helpers\Binance\BinanceWebSocketApi;
use App\Jobs\Payment\PaymentHandleJob;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Amp\Websocket\Client;

class PaymentListenCommand extends Command
{
    protected $signature = 'listen:payment {payment}';

    protected $description = 'Command description';


    public function handle(BinanceWebSocketApi $binance_web_socket_api)
    {
        $payment_id = $this->argument('payment');
        $payment = Payment::query()->findOrFail($payment_id);
        Log::alert("geldi $payment_id");

        dump('Payment kaydı getirildi.', $payment);

        // ListenKey i getirir.
        $binance_web_socket_api->setAPI($payment[Payment::SUB_ACCOUNT_API_KEY], $payment[Payment::SUB_ACCOUNT_API_SECRET]);
        $listenKey = $binance_web_socket_api->getListenKey();

        dump("- Listen key alındı. [$listenKey]");

        \Amp\Loop::run(function () use ( $payment, $listenKey ) {

            $connection = yield Client\connect("wss://stream.binance.com:9443/ws/$listenKey");
            dump("Bağlantı başarılı");

            $message = yield $connection->receive();
            dump('Data geldi.');

            $payload = yield $message->buffer();

            dump('Data içeriği:', $payload);

            dispatch(new PaymentHandleJob($payment, $payload));

            $connection->close();
            dump('-Bağlantı kapatıldı.');
        });

        dump('Akış sonlandı.');
    }
}
