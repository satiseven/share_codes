<?php

namespace App\Models;

use App\Casts\CryptCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    const ID                     = 'id';
    const ORDER_ID               = 'order_id';
    const MAIN_SUB_ACCOUNT_ID    = 'main_sub_account_id';
    const STATUS                 = 'status';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const CRYPTO                 = 'crypto';
    const CRYPTO_AMOUNT          = 'crypto_amount';
    const NETWORK                = 'network';
    const FIXED_AMOUNT           = 'fixed_amount';
    const FIXED_ASSET            = 'fixed_asset';
    const TXN_ID                 = 'txn_id';
    const EXPIRE_MINUTE          = 'expire_minute';
    const SUB_ACCOUNT_ID         = 'sub_account_id';
    const SUB_ACCOUNT_EMAIL      = 'sub_account_email';
    const SUB_ACCOUNT_API_KEY    = 'sub_account_api_key';
    const SUB_ACCOUNT_API_SECRET = 'sub_account_api_secret';
    const DEPOSIT_ADDRESS        = 'deposit_address';
    const DEPOSIT_URL            = 'deposit_url';

    protected $fillable = [
        self::ORDER_ID,
        self::MAIN_SUB_ACCOUNT_ID,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::CRYPTO,
        self::CRYPTO_AMOUNT,
        self::NETWORK,
        self::FIXED_AMOUNT,
        self::FIXED_ASSET,
        self::TXN_ID,
        self::EXPIRE_MINUTE,
        self::SUB_ACCOUNT_ID,
        self::SUB_ACCOUNT_EMAIL,
        self::SUB_ACCOUNT_API_KEY,
        self::SUB_ACCOUNT_API_SECRET,
        self::DEPOSIT_ADDRESS,
        self::DEPOSIT_URL,
    ];

    protected $casts = [
        self::SUB_ACCOUNT_ID         => CryptCast::class,
        self::SUB_ACCOUNT_EMAIL      => CryptCast::class,
        self::SUB_ACCOUNT_API_KEY    => CryptCast::class,
        self::SUB_ACCOUNT_API_SECRET => CryptCast::class,
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
