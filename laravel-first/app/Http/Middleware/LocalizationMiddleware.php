<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LocalizationMiddleware
{
    public function handle( Request $request, Closure $next )
    {
        $language_code = $request->route()->parameter('locale');

        if ( ! in_array($language_code, config('app.supported_locales')) )
            $language_code = Str::of($request->getPreferredLanguage())->explode('_')->first();

        $request->route()->setParameter('locale', $language_code);
        URL::defaults([ 'locale' => $language_code ]);

        app()->setLocale($language_code);

        return $next($request);
    }
}

