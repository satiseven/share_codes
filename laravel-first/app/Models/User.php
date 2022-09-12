<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail, HasLocalePreference
{
    use Notifiable;

    const ID                = 'id';
    const NAME              = 'name';
    const SURNAME           = 'surname';
    const EMAIL             = 'email';
    const PASSWORD          = 'password';
    const REMEMBER_TOKEN    = 'remember_token';
    const EMAIL_VERIFIED_AT = 'email_verified_at';

    protected $fillable = [
        self::NAME,
        self::SURNAME,
        self::EMAIL,
        self::PASSWORD,
        self::EMAIL_VERIFIED_AT,
    ];

    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN,
    ];

    protected $casts = [
        self::EMAIL_VERIFIED_AT => 'datetime',
    ];

    public function preferredLocale()
    {
        return config('app.locale');
    }
}
