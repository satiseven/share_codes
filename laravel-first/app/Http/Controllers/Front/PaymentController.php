<?php

namespace App\Http\Controllers\Front;

use App\Enums\Payment\PaymentStatusEnum;
use App\Helpers\Binance\BinanceService;
use App\Http\Controllers\Controller;
use App\Jobs\Payment\PaymentListenJob;
use App\Models\MainSubAccount;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function start( Request $request, $order )
    {
        $order = $request->order;
        $payment = $order->load('payment')->payment;

        dispatch(new PaymentListenJob($payment));
        return response([], 200);
    }


    public function subscriber( Request $request, BinanceService $binance_service )
    {
        $payment_id = $request->payment_id;
        $data       = $request->data;
        $success    = $request->success;

        $this->multiple_process(function () use ( $binance_service, $payment_id, $data, $success ) {
            $payment = Payment::query()->where(Payment::STATUS, PaymentStatusEnum::WAITING)->findOrFail($payment_id);

            /*
                {
                    "e": "outboundAccountPosition",
                    "E": 1647271059623,
                    "u": 1647271059623,
                    "B": {
                            "a":"USDT",
                            "f":"40.00000000",
                            "l":"0.00000000"
                         }
                }
            */

            if ( ! $data && ! $success ) {
                $payment->update([
                    Payment::STATUS => PaymentStatusEnum::EXPIRED,
                ]);
            }

            $data_event  = $data['e'] === 'outboundAccountPosition';
            $data_crypto = $data['B']['a'];
            $data_amount = $data['B']['f'];

            if ( $data &&
                $success &&
                $data_event == 'outboundAccountPosition' &&
                $payment[Payment::CRYPTO] === $data_crypto &&
                $payment[Payment::CRYPTO_AMOUNT] <= $data_amount
            ) {

                $main_sub_account = MainSubAccount::query()->where(MainSubAccount::IS_ACTIVE, TRUE)->first();

                $from_id        = $payment[Payment::SUB_ACCOUNT_ID];
                $to_id          = $main_sub_account->value(MainSubAccount::SUB_ACCOUNT_ID);
                $asset          = $data_crypto;
                $amount         = $data_amount;
                $client_tran_id = $payment[Payment::ID];

                /// Bir sub accounta transfer yapılacak.
                $transfer_response = $binance_service->sub_account_transfer($from_id, $to_id, $asset, $amount, $client_tran_id);
                $txn_id            = $transfer_response['txnId'];
                /* $transfer_response kayıt edilecek. payment e
                {
                    "txnId":"2966662589",
                    "clientTranId":"abc"
                }
                */

                // main sub account api key ve secret set edilecek.
                $main_sub_account_api_key    = $main_sub_account[MainSubAccount::SUB_ACCOUNT_API_KEY];
                $main_sub_account_api_secret = $main_sub_account[MainSubAccount::SUB_ACCOUNT_API_SECRET];

                $binance_service->setAPI($main_sub_account_api_key, $main_sub_account_api_secret);

                // gelen değer USDT cinsinde değilse USDT ye çevirme işlemi yapılacak. gelen response FIXED_AMOUNT ve FIXED_CRYPTO a kayıt edilecek.
                $fixed_asset  = 'USDT';
                $fixed_amount = $amount;

                // Böyle bir trade çifti var mı kontrol edilecek.
                if ( $asset !== $fixed_asset ) {
                    $order_response = $binance_service->createNewOrder("{$asset}{$fixed_asset}", $amount);
                    $fixed_amount   = $order_response['cummulativeQuoteQty'];
                }

                $payment->update([
                    Payment::MAIN_SUB_ACCOUNT_ID => $main_sub_account[MainSubAccount::ID],
                    Payment::STATUS              => PaymentStatusEnum::RECEIPT,
                    Payment::FIXED_AMOUNT        => $fixed_amount,
                    Payment::FIXED_ASSET         => $fixed_asset,
                    Payment::TXN_ID              => $txn_id,
                ]);
            }
        });
    }
}
