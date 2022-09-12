<?php

namespace App\Http\Middleware\Payment;

use App\Helpers\Petite\CryptHelper;
use App\Models\Order;
use Closure;
use Illuminate\Http\Request;

class BuyyableMiddleware
{
    public function handle( Request $request, Closure $next )
    {
        try {
            $auth_id  = auth()->id() ?? ( ( $query_token = $request->query('token') ) ? CryptHelper::decode_parameter($query_token) : NULL );
            $order_id = CryptHelper::decode_parameter($request->order_id);
            $order    = Order::query()
                //->whereDate(Order::CREATED_AT, '<=', now()->addMinutes(30)) // TODO DEPLOY EDERKEN AÇILACAK.
                //->whereTime(Order::CREATED_AT, '<=', now()->addMinutes(30)) // TODO DEPLOY EDERKEN AÇILACAK.
                ->where(Order::USER_ID, $auth_id)
                ->find($order_id);

            if ( ! $order ) {
                return redirect()->route('home');
            }
            //abort_unless((bool) $order, Response::HTTP_EXPECTATION_FAILED);

            $request->offsetSet('order', $order);
        } catch ( \Exception $exception ) {
            dd([
                'BuyyableMiddleware.php',
                $exception->getMessage(),
                $exception->getLine(),
                $exception->getTraceAsString(),
            ]);
        }


        return $next($request);
    }
}
