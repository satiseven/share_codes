<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    const ID                        = 'id';
    const IS_ACTIVE                 = 'is_active';
    const COIN_ID                   = 'coin_id';
    const ADDRESS_REGEX             = 'address_regex';            // "^(bnb1)[0-9a-z]{38}$",
    const COIN                      = 'coin';                     // "BTC",
    const DEPOSIT_DESC              = 'deposit_desc';             // "Wallet Maintenance, Deposit Suspended", // shown only when "depositEnable" is false.
    const DEPOSIT_ENABLE            = 'deposit_enable';           // FALSE,
    const IS_DEFAULT                = 'is_default';               // FALSE,
    const MEMO_REGEX                = 'memo_regex';               // "^[0-9A-Za-z\\-_]{1,120}$",
    const MIN_CONFIRM               = 'min_confirm';              // 1,  // min number for balance confirmation
    const NAME                      = 'name';                     // "BEP2",
    const NETWORK                   = 'network';                  // "BNB",
    const RESET_ADDRESS_STATUS      = 'reset_address_status';     // FALSE,
    const SPECIAL_TIPS              = 'special_tips';             // "Both a MEMO and an Address are required to successfully deposit your BEP2-BTCB tokens to Binance.",
    const UNLOCK_CONFIRM            = 'un_lock_confirm';          // 0,  // confirmation number for balance unlock
    const WITHDRAW_DESC             = 'withdraw_desc';            // "Wallet Maintenance, Withdrawal Suspended", // shown only when "withdrawEnable" is false.
    const WITHDRAW_ENABLE           = 'withdraw_enable';          // FALSE,
    const WITHDRAW_FEE              = 'withdraw_fee';             // "0.00000220",
    const WITHDRAW_INTEGER_MULTIPLE = 'withdraw_integer_multiple';// "0.00000001",
    const WITHDRAW_MAX              = 'withdraw_max';             // "9999999999.99999999",
    const WITHDRAW_MIN              = 'withdraw_min';             // "0.00000440",
    const SAME_ADDRESS              = 'same_address';             // TRUE  // If the coin needs to provide memo to withdraw

    protected $fillable = [
        self::IS_ACTIVE,
        self::COIN_ID,
        self::ADDRESS_REGEX,
        self::COIN,
        self::DEPOSIT_DESC,
        self::DEPOSIT_ENABLE,
        self::IS_DEFAULT,
        self::MEMO_REGEX,
        self::MIN_CONFIRM,
        self::NAME,
        self::NETWORK,
        self::RESET_ADDRESS_STATUS,
        self::SPECIAL_TIPS,
        self::UNLOCK_CONFIRM,
        self::WITHDRAW_DESC,
        self::WITHDRAW_ENABLE,
        self::WITHDRAW_FEE,
        self::WITHDRAW_INTEGER_MULTIPLE,
        self::WITHDRAW_MAX,
        self::WITHDRAW_MIN,
        self::SAME_ADDRESS,
    ];

}
