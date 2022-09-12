<?php

namespace App\Helpers\Binance;

class BinanceWebSocketApi extends BinanceApiBase
{

    // POST : Yeni bir listenKey oluşturur.
    // https://binance-docs.github.io/apidocs/spot/en/#user-data-streams
    public function getListenKey( string $method = 'POST' ) { return $this->publicRequest('api/v3/userDataStream', [], $method)['listenKey']; }
}
