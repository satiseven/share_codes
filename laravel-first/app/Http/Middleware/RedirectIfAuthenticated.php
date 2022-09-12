<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param string|null              ...$guards
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle( Request $request, Closure $next, ...$guards )
    {
        /*
        if ( \auth()->guard(GuardEnum::ADMIN)->check() ) {
            return redirect()->route('admin.dashboard');
        } else if ( \auth()->guard(GuardEnum::USER)->check() ) {
            return redirect()->route('home');
        }*/

        $guards = empty($guards) ? [ NULL ] : $guards;
        foreach ($guards as $guard) {
            if ( Auth::guard($guard)->check() )
                return redirect(RouteServiceProvider::HOME);
        }

        return $next($request);
    }
}
