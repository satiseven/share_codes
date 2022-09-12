<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TickerType extends Model
{
    const ID      = 'id';
    const SYMBOL  = 'symbol';
    const FILTERS = 'filters';

    protected $fillable = [
        self::SYMBOL,
        self::FILTERS,
    ];

    protected $casts = [
        self::FILTERS => 'json',
    ];
}
