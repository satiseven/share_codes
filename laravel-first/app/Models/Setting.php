<?php

namespace App\Models;

use App\Casts\CryptCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use SoftDeletes;

    public $timestamps = FALSE;

    public $incrementing = FALSE;

    protected $primaryKey = self::KEY;

    const KEY   = '_key';
    const VALUE = 'value';

    protected $fillable = [
        Setting::KEY,
        Setting::VALUE,
    ];

    protected $casts = [
        Setting::KEY   => 'string',
        Setting::VALUE => CryptCast::class,
    ];
}
