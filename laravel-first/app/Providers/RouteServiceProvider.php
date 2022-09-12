<?php

namespace App\Providers;

use App\Enums\Auth\GuardEnum;
use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/';

    public function boot()
    {
        $this->configureRateLimiting();
        $base_domain         = SettingHelper::app()->get(SettingKeysEnum::APP_DOMAIN);
        $admin_subdomain = SettingHelper::app()->get(SettingKeysEnum::APP_ADMIN_SUBDOMAIN);
        $admin_domain    = $domain = '';

        if ( env('APP_ENV', 'test') == 'test' ) {
            $admin_domain = "https://$admin_subdomain.$base_domain";
            $domain       = "https://dev.$base_domain";
 /*           dd([
                'env'           => 'test',
                '$admin_domain' => $admin_domain,
                '$domain' => $domain,
            ]);*/
        } else if ( env('APP_ENV', 'test') == 'production' ) {
            $admin_domain = $admin_subdomain . $base_domain;
            $domain       = $base_domain;
/*            dd([
                'env'           => 'production',
                '$admin_domain' => $admin_domain,
                '$domain'       => $domain,
            ]);*/
        } else if ( $this->app->isLocal() ) {
            $admin_domain = $admin_subdomain . $base_domain;
            $domain       = $base_domain;
/*            dd([
                'env'           => 'local',
                '$admin_domain' => $admin_domain,
                '$domain'       => $domain,
            ]);*/
        }
        $this->routes(function () use ( $domain, $admin_domain ) {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware([ 'web', 'auth:' . GuardEnum::ADMIN ])
                ->domain($admin_domain)
                ->group(base_path('routes/admin/web.php'));

            Route::middleware('web')
                ->domain($admin_domain)
                ->group(base_path('routes/admin/auth.php'));

            Route::middleware('web')
                ->domain($domain)
                ->group(base_path('routes/front/web.php'));

            Route::middleware('web')
                ->domain($domain)
                ->group(base_path('routes/front/auth.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function ( Request $request ) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
