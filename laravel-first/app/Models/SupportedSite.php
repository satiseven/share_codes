<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportedSite extends Model
{
    use SoftDeletes;

    const ID                = 'id';
    const NAME              = 'name';
    const BASE_URL          = 'base_url';
    const EXAMPLE_LINK      = 'example_link';
    const HELPER_CLASS      = 'helper_class';
    const IMAGE_SELECTOR    = 'image_selector';
    const TITLE_SELECTOR    = 'title_selector';
    const PRICE_SELECTOR    = 'price_selector';
    const CURRENCY_SELECTOR = 'currency_selector';

    public $timestamps = FALSE;

    protected $fillable = [
        self::NAME,
        self::BASE_URL,
        self::EXAMPLE_LINK,
        self::HELPER_CLASS,
        self::IMAGE_SELECTOR,
        self::TITLE_SELECTOR,
        self::PRICE_SELECTOR,
        self::CURRENCY_SELECTOR,
    ];

    protected $casts = [
        self::IMAGE_SELECTOR    => 'json',
        self::TITLE_SELECTOR    => 'json',
        self::PRICE_SELECTOR    => 'json',
        self::CURRENCY_SELECTOR => 'json',
    ];
}
