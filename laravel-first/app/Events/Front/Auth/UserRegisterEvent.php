<?php

namespace App\Events\Front\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegisterEvent extends Registered implements ShouldQueue
{
    use Dispatchable, SerializesModels;
}
