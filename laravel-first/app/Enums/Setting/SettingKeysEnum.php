<?php

namespace App\Enums\Setting;

class SettingKeysEnum
{
    const APP_NAME                     = 'app_name';
    const APP_DEBUG                    = 'app_debug';
    const APP_URL                      = 'app_url';
    const APP_DOMAIN                   = 'app_domain';
    const APP_ADMIN_SUBDOMAIN          = 'app_admin_subdomain';
    const MAIL_FROM_ADDRESS            = 'mail_from_address';
    const ORDER_MAIL_ADDRESS           = 'order_mail_address';
    const ORDERS_SLACK_URL             = 'orders_slack_url';
    const COMMISSION_PERCENT           = 'commission_percent';
    const MIN_ORDER_USD_AMOUNT         = 'min_order_usd_amount';
    const RATE_AED                     = 'rate_aed';
    const PAYMENT_EXPIRED_MINUTE       = 'payment_expired_minute';
    const PAYMENT_SUBSCRIBER_PASSWORD  = 'payment_subscriber_password';
    const BINANCE_API_URL              = 'binance_api_url';
    const BINANCE_SOCKET_URL           = 'binance_socket_url';
    const BINANCE_SUB_ACCOUNT_TAG      = 'binance_sub_account_tag';
    const BINANCE_SUB_ACCOUNT_MAIN_TAG = 'binance_sub_account_main_tag';
    const BINANCE_API_KEY              = 'binance_api_key';
    const BINANCE_API_SECRET           = 'binance_api_secret';
}
