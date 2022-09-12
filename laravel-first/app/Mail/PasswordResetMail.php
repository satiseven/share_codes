<?php

namespace App\Mail;

use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $token;

    public function __construct( $token )
    {
        $this->token = $token;
    }

    public function build()
    {
        return $this
            ->from(SettingHelper::app()->get(SettingKeysEnum::MAIL_FROM_ADDRESS), SettingHelper::app()->get(SettingKeysEnum::APP_NAME))
            ->subject("Password Reset Mail")
            ->markdown('front.mail.auth.password_reset');
    }
}
