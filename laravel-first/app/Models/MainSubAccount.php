<?php

namespace App\Models;

use App\Casts\CryptCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MainSubAccount extends Model
{
    use SoftDeletes;

    const ID                     = 'id';
    const SUB_ACCOUNT_ID         = 'sub_account_id';
    const SUB_ACCOUNT_EMAIL      = 'sub_account_email';
    const SUB_ACCOUNT_API_KEY    = 'sub_account_api_key';
    const SUB_ACCOUNT_API_SECRET = 'sub_account_api_secret';
    const IS_ACTIVE              = 'is_active';

    // şifrelenecek değerler için custom cast yazılacak.

    protected $casts = [
        self::SUB_ACCOUNT_ID         => CryptCast::class,
        self::SUB_ACCOUNT_EMAIL      => CryptCast::class,
        self::SUB_ACCOUNT_API_KEY    => CryptCast::class,
        self::SUB_ACCOUNT_API_SECRET => CryptCast::class,
    ];

}
