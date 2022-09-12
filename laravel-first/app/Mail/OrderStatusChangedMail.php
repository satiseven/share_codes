<?php

namespace App\Mail;

use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $subject;
    public $to_address;
    public $to_name;

    public function __construct( $order, $subject, $to_address, $to_name )
    {
        $this->order      = $order;
        $this->subject    = $subject;
        $this->to_address = $to_address;
        $this->to_name    = $to_name;
    }

    public function build()
    {
        return $this
            ->subject($this->subject)
            ->from(SettingHelper::app()->get(SettingKeysEnum::MAIL_FROM_ADDRESS), SettingHelper::app()->get(SettingKeysEnum::APP_NAME))
            ->to($this->to_address, $this->to_name)
            ->markdown('front.mail.order.status_changed', [
                'order'   => $this->order,
                'subject' => $this->subject,
            ]);
    }

    public function preferredLocale()
    {
        return $this->locale;
    }
}
