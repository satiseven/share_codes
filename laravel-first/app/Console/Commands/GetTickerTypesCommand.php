<?php

namespace App\Console\Commands;

use App\Helpers\Binance\BinanceService;
use App\Models\TickerType;
use Illuminate\Console\Command;

class GetTickerTypesCommand extends Command
{
    protected $signature = 'get:ticker_types';

    protected $description = 'Binance\'daki tüm trade tiplerinin listesini alır.';

    public function handle( BinanceService $binance_service )
    {
        $ticker_types = $binance_service->getExchangeInfo()['symbols'];
        $ticker_types = collect($ticker_types)->keyBy('symbol');

        $filter_data = [];

        foreach ($ticker_types as $key => $ticker_type) {

            $filters = collect($ticker_type['filters']);

            $filter_data[$key] = [
                'symbol'  => $key,
                'filters' => [
                    'LOT_SIZE'     => $filters->where('filterType', '=', 'LOT_SIZE')->first(),
                    'MIN_NOTIONAL' => $filters->where('filterType', '=', 'MIN_NOTIONAL')->first(),
                ],
            ];
        }
        // TODO : DB'de bununla ilgili bir tablo olacak ve günde 1 kez güncellemesi yapılacak. Buradaki amaç önyüze böyle bir trade tipi var mı yok mu cevabını verebilmeyi sağlamak.
        TickerType::query()->truncate();
        foreach ($filter_data as $filter_datum) {
            TickerType::query()->create($filter_datum);
        }
    }
}
