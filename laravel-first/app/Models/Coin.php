<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coin extends Model
{
    const ID                  = 'id';
    const IS_ACTIVE           = 'is_active';
    const PRECISION           = 'precision';
    const COIN                = 'coin';
    const DEPOSIT_ALL_ENABLE  = 'deposit_all_enable';
    const WITHDRAW_ALL_ENABLE = 'withdraw_all_enable';
    const NAME                = 'name';
    const FREE                = 'free';
    const LOCKED              = 'locked';
    const FREEZE              = 'freeze';
    const WITHDRAWING         = 'withdrawing';
    const IPOING              = 'ipoing';
    const IPOABLE             = 'ipoable';
    const STORAGE             = 'storage';
    const IS_LEGAL_MONEY      = 'is_legal_money';
    const TRANDING            = 'trading';

    protected $fillable = [
        self::IS_ACTIVE,
        self::PRECISION,
        self::COIN,
        self::DEPOSIT_ALL_ENABLE,
        self::WITHDRAW_ALL_ENABLE,
        self::NAME,
        self::FREE,
        self::LOCKED,
        self::FREEZE,
        self::WITHDRAWING,
        self::IPOING,
        self::IPOABLE,
        self::STORAGE,
        self::IS_LEGAL_MONEY,
        self::TRANDING,
    ];

    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    public function scopeActiveNetworks( $query )
    {
        return $query->with([
            'networks' => function ( $query ) {
                return $query->where([
                    [ Network::DEPOSIT_ENABLE, TRUE ],
                    [ Network::IS_ACTIVE, TRUE ],
                ])->select(Network::COIN_ID, Network::NETWORK);
            },
        ]);
    }
}
