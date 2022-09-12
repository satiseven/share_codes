<?php

namespace App\Jobs\Payment;

use App\Jobs\BaseJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentHandleJob extends BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;
    protected $payload;

    public function __construct( $payment, $payload )
    {
        $this->payment = $payment;
        $this->payload = $payload;
        dump('* PaymentHandleJob->__construct çalıştı.');
    }

    public function handle()
    {
        $this->multiple_process(function () {
            $payment = $this->payment;
            $payload = $this->payload;

            dump('* Handle\'a geldi.');
        });
    }
}
