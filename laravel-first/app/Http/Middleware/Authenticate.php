<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return string|null
     */
    protected function redirectTo( $request )
    {
        if ( ! $request->expectsJson() ) {
            //if ( Str::contains($request->url(), 'devadmin.') ) {
            //    return route('admin.dashboard');
            //} else if ( Str::contains($request->url(), 'dev.') ) {
            return route('home');
            //}
        }
    }
}
