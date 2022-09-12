<?php

namespace App\Models;

use App\Helpers\Petite\RandomHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    const ID                = 'id';
    const SUPPORTED_SITE_ID = 'supported_site_id';
    const USER_ID           = 'user_id';
    const MAC_ADDRESS       = 'mac_address';
    const NUMBER            = 'number';
    const LINK              = 'link';
    const STATUS            = 'status';
    const PRODUCT           = 'product';
    const INVOICE_ADDRESS   = 'invoice_address';
    const SHIPPING_ADDRESS  = 'shipping_address';
    const FIRST_NAME        = 'first_name';
    const LAST_NAME         = 'last_name';
    const COMPANY_NAME      = 'company_name';
    const PHONE             = 'phone';
    const NOTES             = 'notes';
    const CRYPTO            = 'crypto';
    const NETWORK           = 'network';

    protected $with = [ 'payment' ];

    protected $fillable = [
        self::SUPPORTED_SITE_ID,
        self::USER_ID,
        self::MAC_ADDRESS,
        self::NUMBER,
        self::LINK,
        self::STATUS,
        self::PRODUCT,
        self::INVOICE_ADDRESS,
        self::SHIPPING_ADDRESS,
        self::FIRST_NAME,
        self::LAST_NAME,
        self::COMPANY_NAME,
        self::PHONE,
        self::NOTES,
        self::CRYPTO,
        self::NETWORK,
    ];

    protected $casts = [
        self::PRODUCT          => 'json',
        self::INVOICE_ADDRESS  => 'json',
        self::SHIPPING_ADDRESS => 'json',
    ];

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function createNumber(): string
    {
        while (self::query()->where(self::NUMBER, $random = RandomHelper::numeric(6))->exists()) ;
        return $random;
    }
}
