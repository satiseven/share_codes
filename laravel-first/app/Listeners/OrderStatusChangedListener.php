<?php

namespace App\Listeners;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Setting\SettingKeysEnum;
use App\Events\OrderStatusChangedEvent;
use App\Helpers\Setting\SettingHelper;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderStatusChangedListener implements ShouldQueue
{
    public function handle( OrderStatusChangedEvent $event )
    {
        $order      = $event->order;
        $status     = $order[Order::STATUS];
        $user       = $order->user;
        $to_address = $user[User::EMAIL];
        $to_name    = $user[User::NAME] . ' ' . $user[User::SURNAME];
        $subject    = '';

        $is_mail = TRUE;
        switch ( $status ) {
            case OrderStatusEnum::SEARCH:
                $is_mail = FALSE; // mail gönderilmeyecek.
                break;
            case OrderStatusEnum::ORDERED:
                // Admine mail gidecek.
                $subject    = 'New order!';
                $to_address = SettingHelper::app()->get(SettingKeysEnum::ORDER_MAIL_ADDRESS);
                $to_name    = 'Zatrun Admin';
                Log::alert("$to_address mail gönderildi.");
                break;
            case OrderStatusEnum::ACCEPTED:
                // Kullanıcıya mail gidecek.
                $subject = 'Order accepted!';
                break;
            case OrderStatusEnum::PURCHASED:
                // Kullanıcıya mail gidecek.
                $subject = 'Order purchased!';

                break;
            case OrderStatusEnum::SHIPMENT:
                // Kullanıcıya mail gidecek.
                $subject = 'Order shipped!';

                break;
            case OrderStatusEnum::DELIVERED:
                // Kullanıcıya mail gidecek.
                $subject = 'Order delivered!';

                break;
            case OrderStatusEnum::CANCELED:
                // Kullanıcıya mail gidecek.
                $subject = 'Order canceled!';

                break;
        }

        if ( $is_mail ) {
            Mail::send(new OrderStatusChangedMail($order, $subject, $to_address, $to_name));
            Log::debug('mail gönderildi');
        }
    }
}
