<?php

namespace App\Helpers\Binance;

class BinanceService
{
    protected $binance_api;

    public function __construct( BinanceApi $binance_api ) { $this->binance_api = $binance_api; }

    public function setAPI( $api_key, $api_secret ) { $this->binance_api->setAPI($api_key, $api_secret); }

    // GET : Sub account listesini verir.
    // POST : Yeni bir sub account oluşturur.
    public function subAccount( string $tag, string $method = 'POST' )
    {
        $data = [ 'tag' => $tag ];
        return $this->binance_api->subAccount($data, $method);
    }

    // GET : Sub account API key bilgisi verir.
    // POST : Sub account API key ve secret bilgisi oluşturur.
    public function subAccountApi( string $sub_account_id, string $method = 'POST' )
    {
        $data = [ 'subAccountId' => $sub_account_id, 'canTrade' => TRUE ];
        return $this->binance_api->subAccountApi($data, $method);
    }

    // GET : Kullanıcı varlık listesini verir.
    public function getSubAccountAssets( string $email, $symbols = NULL ): array
    {
        $data = [ 'email' => $email ];

        $balances = $this->binance_api->getSubAccountAssets($data)['balances'];

        $balances = collect($balances)->keyBy('asset')->toArray();
        if ( is_array($symbols) ) {
            $fitered = [];

            foreach ($symbols as $symbol) {
                $fitered[$symbol] = isset($balances[$symbol]) ? $balances[$symbol] : NULL;
            }

            $balances = $fitered;
        } else if ( is_string($symbols) ) {
            $balances = $balances[$symbols];
        }

        return $balances;
    }

    // GET : Varlık dönüşümü yapar.
    public function getSymbolPriceTicker( $symbol = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getSymbolPriceTicker($data);
    }

    // GET : Kullanıcının varlıklarının toplam değerini BTC cinsinden verir.
    public function getSpotSummary( string $sub_account_id )
    {
        $data = [ 'subAccountId' => $sub_account_id ];

        return $this->binance_api->getSpotSummary($data)['data'][0]['totalBalanceOfBtc'];
    }

    // GET : Kullanıcı deposit adresini verir.
    public function getDepositAddress( string $coin, string $network = NULL )
    {
        $data = [ 'coin' => $coin, 'network' => $network ];

        return $this->binance_api->getDepositAddress($data);
    }

    // POST : İşlem çiflerinde alım satım yapar.
    public function createNewOrder( string $symbol, string $quantity, string $side = 'SELL', string $type = 'MARKET' )
    {
        $data = [
            'symbol'                                       => $symbol,
            'type'                                         => $type,
            'side'                                         => $side,
            $side === 'BUY' ? 'quoteOrderQty' : 'quantity' => $quantity,
        ];

        return $this->binance_api->createNewOrder($data);
    }

    // Cari hesap bilgilerini alın.
    public function getAccountInfo() { return $this->binance_api->getAccountInfo(); }

    // Kullanıcı için jeton bilgilerini (para yatırma ve çekme için kullanılabilir) alın. Sapi Endpoint'i kullanır.
    public function getUserCoinsInfo() { return $this->binance_api->getUserCoinsInfo(); }

    // Kullanıcı hesabının para yatırma geçmişini alın. Sapi Endpoint'i kullanır.
    public function getDepositHistory() { return $this->binance_api->getDepositHistory(); }

    // Kullanıcı hesabının para çekme geçmişini alın. Sapi Endpoint'i kullanır.
    public function getWithdrawHistory() { return $this->binance_api->getWithdrawHistory(); }

    // Alt hesap para yatırma geçmişini getir.
    public function getSubAccountDepositHistory( string $email, string $coin = NULL, int $limit = 5, int $page = 0, $start_time = NULL, $end_time = NULL, $status = 1 )
    {
        /// Status: /// 0 = pending /// 1 = success /// 6 = credited but cannot withdraw

        $data = [
            'email'      => $email,
            'coin'       => $coin,
            'limit'      => $limit,
            'offset'     => $page,
            'start_time' => strtotime($start_time),
            'end_time'   => strtotime($end_time),
            'status'     => $status,
        ];

        return $this->binance_api->getSubAccountDepositHistory($data);
    }

    // Tüm cari hesap siparişlerini alın; etkin, iptal edilmiş veya doldurulmuş.
    public function getAllOrders( $symbol = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getAllOrders($data);
    }

    // Tüm cari hesap açık siparişlerini bir sembol üzerinde alın. Buna sembol olmadan erişirken dikkatli olun.
    public function getOpenOrders( $symbol = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getOpenOrders($data);
    }

    // Belirli bir sembolün ticaret geçmişini alın.
    public function getTrades( $symbol = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getTrades($data);
    }

    // Bir siparişin durumunu alın.
    public function getOrderStatus( $symbol = NULL, $orderId = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL, 'orderId' => $orderId ];

        return $this->binance_api->getOrderStatus($data);
    }

    // Binance Sistem Durumunu Alın. Sapi Endpoint'i kullanır.
    public function getSystemStatus()
    {
        return $this->binance_api->getSystemStatus();
    }

    // Binance Sunucu Saatini Alın.
    public function getTime() { return $this->binance_api->getTime(); }

    // Binance Exchange Bilgilerini Alın.
    public function getExchangeInfo( $symbol = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getExchangeInfo($data);
    }

    // Belirli bir sembol için Binance Sipariş Defterini alın.
    public function getOrderBook( $symbol )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getOrderBook($data);
    }

    // Belirli bir sembol için Ortalama Fiyat alın.
    public function getAvgPrice( $symbol = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getAvgPrice($data);
    }

    // 24 saat Ticker Fiyat Değişikliği İstatistiklerini Alın. Sembol gönderilmezse, bir dizide tüm semboller için işaretler döndürülür.
    public function getTicker( $symbol = NULL )
    {
        $data = [ 'symbol' => $symbol ? strtoupper($symbol) : NULL ];

        return $this->binance_api->getTicker($data);
    }

    // Sub Accountlar arası transfer yapmayı sağlar. Master API KEY ve SECRET kullanır.
    public function sub_account_transfer( string $from_id, string $to_id, string $asset, string $amount, string $client_tran_id )
    {
        $data = [
            'fromId'       => $from_id,
            'toId'         => $to_id,
            'asset'        => $asset,
            'amount'       => $amount,
            'clientTranId' => $client_tran_id,
        ];

        return $this->binance_api->sub_account_transfer($data);
    }
}
