<?php

namespace App\Enums\Payment;

class PaymentStatusEnum
{
    const WAITING = 'waiting';
    const EXPIRED = 'expired';
    const CANCEL  = 'cancel';
    const RECEIPT = 'receipt';
}
