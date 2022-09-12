<?php

namespace App\Helpers\Migration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MigrationHelper
{
    public static function indexer( Blueprint $table, string ...$keys )
    {
        $data        = Arr::crossJoin($keys, $keys, $keys, $keys, $keys);
        $transmitter = [];
        $results     = [];

        foreach ($data as $value) {
            $transmitter[] = collect($value)->unique();
        }

        $data = $transmitter;

        foreach ($data as $keys) {
            foreach ($keys as $item) {
                $keys = Arr::sort($keys);
                $key                  = implode('_', $keys);
                $results[$key][$item] = $item;
            }
        }

        $results = collect($results)->unique()->toArray();
        foreach ($results as $key => $result) {
            $table->index($result, "{$key}_index");
        }
    }

}
