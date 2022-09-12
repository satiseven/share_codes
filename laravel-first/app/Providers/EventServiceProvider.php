<?php

namespace App\Providers;

use App\Events\Front\Auth\UserRegisterEvent;
use App\Events\OrderStatusChangedEvent;
use App\Listeners\Front\Auth\SendEmailVerificationNotificationListener;
use App\Listeners\OrderStatusChangedListener;
use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserRegisterEvent::class       => [ SendEmailVerificationNotificationListener::class ],
        OrderStatusChangedEvent::class => [ OrderStatusChangedListener::class ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Order::observe(OrderObserver::class);
    }
}
