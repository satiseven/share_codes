<?php

namespace App\Listeners\Front\Auth;

use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendEmailVerificationNotificationListener extends SendEmailVerificationNotification implements ShouldQueue
{

}
