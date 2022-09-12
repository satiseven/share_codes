<?php

namespace App\Http\Middleware\Payment;

use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriberCheckMiddleware
{
    public function handle( Request $request, Closure $next )
    {
        abort_unless($request->password === SettingHelper::app()->get(SettingKeysEnum::PAYMENT_EXPIRED_MINUTE), Response::HTTP_PAYMENT_REQUIRED);

        return $next($request);
    }
}
