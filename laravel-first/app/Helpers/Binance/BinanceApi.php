<?php

namespace App\Helpers\Binance;

class BinanceApi extends BinanceApiBase
{
    // GET : Sub account listesini verir.
    // POST : Yeni bir sub account oluşturur.
    // https://binance-docs.github.io/Brokerage-API/Brokerage_Operation_Endpoints/#create-a-sub-account
    public function subAccount( array $data, string $method = 'POST' ) { return $this->privateRequest('sapi/v1/broker/subAccount', $data, $method); }

    // GET : Sub account API key bilgisi verir.
    // POST : Sub account API key ve secret bilgisi oluşturur.
    // https://binance-docs.github.io/Brokerage-API/Brokerage_Operation_Endpoints/#create-api-key-for-sub-account
    public function subAccountApi( array $data, string $method = 'POST' ) { return $this->privateRequest('sapi/v1/broker/subAccountApi', $data, $method); }

    // GET : Kullanıcı varlık listesini verir.
    // https://binance-docs.github.io/apidocs/spot/en/#query-sub-account-assets-for-master-account
    public function getSubAccountAssets( array $data ) { return $this->privateRequest('sapi/v3/sub-account/assets', $data); }

    // GET : Varlık dönüşümü yapar.
    // https://binance-docs.github.io/apidocs/spot/en/#symbol-price-ticker
    public function getSymbolPriceTicker( array $data ) { return $this->publicRequest('api/v3/ticker/price', $data); }

    // GET : Kullanıcının varlıklarının toplam değerini BTC cinsinden verir.
    // https://binance-docs.github.io/Brokerage-API/Brokerage_Operation_Endpoints/#query-sub-account-spot-asset-info
    public function getSpotSummary( array $data ) { return $this->privateRequest('sapi/v1/broker/subAccount/spotSummary', $data); }

    // GET : Kullanıcı deposit adresini verir.
    // https://binance-docs.github.io/apidocs/spot/en/#deposit-address-supporting-network-user_data
    public function getDepositAddress( array $data ) { return $this->privateRequest('sapi/v1/capital/deposit/address', $data); }

    // POST : Trade işlemini yapar.
    // https://binance-docs.github.io/apidocs/spot/en/#new-order-trade
    public function createNewOrder( array $data ) { return $this->privateRequest('api/v3/order', $data, 'POST'); }

    // Cari hesap bilgilerini alın.
    public function getAccountInfo() { return $this->privateRequest('api/v3/account'); }

    // Tüm cari hesap siparişlerini alın; etkin, iptal edilmiş veya doldurulmuş.
    public function getAllOrders( array $data ) { return $this->privateRequest('api/v3/allOrders', $data); }

    // Tüm cari hesap açık siparişlerini bir sembol üzerinde alın. Buna sembol olmadan erişirken dikkatli olun.
    public function getOpenOrders( array $data ) { return $this->privateRequest('api/v3/openOrders', $data); }

    // Belirli bir sembolün ticaret geçmişini alın.
    public function getTrades( array $data ) { return $this->privateRequest('api/v3/myTrades', $data); }

    // Bir siparişin durumunu alın.
    public function getOrderStatus( array $data ) { return $this->privateRequest('api/v3/order', $data); }

    // Kullanıcı için jeton bilgilerini (para yatırma ve çekme için kullanılabilir) alın. Sapi Endpoint'i kullanır.
    // https://binance-docs.github.io/apidocs/spot/en/#all-coins-39-information-user_data
    public function getUserCoinsInfo() { return $this->privateRequest('sapi/v1/capital/config/getall'); }

    // Kullanıcı hesabının para yatırma geçmişini alın. Sapi Endpoint'i kullanır.
    public function getDepositHistory() { return $this->privateRequest('api/v1/capital/deposit/hisrec'); }

    // Kullanıcı hesabının para çekme geçmişini alın. Sapi Endpoint'i kullanır.
    public function getWithdrawHistory() { return $this->privateRequest('api/v1/capital/withdraw/history'); }

    // Alt hesap para yatırma geçmişini getir.
    public function getSubAccountDepositHistory( array $data ) { return $this->privateRequest('sapi/v1/capital/deposit/subHisrec', $data); }

    // Binance Sistem Durumunu Alın. Sapi Endpoint'i kullanır.
    public function getSystemStatus() { return $this->publicRequest('api/v1/system/status'); }

    // Binance Sunucu Saatini Alın.
    public function getTime() { return $this->publicRequest('api/v3/time'); }

    // Binance Exchange Bilgilerini Alın.
    public function getExchangeInfo( array $data ) { return $this->publicRequest('api/v3/exchangeInfo', $data); }

    // Belirli bir sembol için Binance Sipariş Defterini alın.
    public function getOrderBook( array $data ) { return $this->publicRequest('api/v3/trades', $data); }

    // Belirli bir sembol için Ortalama Fiyat alın.
    public function getAvgPrice( array $data ) { return $this->publicRequest('api/v3/avgPrice', $data); }

    // 24 saat Ticker Fiyat Değişikliği İstatistiklerini Alın. Sembol gönderilmezse, bir dizide tüm semboller için işaretler döndürülür.
    public function getTicker( array $data ) { return $this->publicRequest('api/v3/ticker/24hr', $data); }

    // Sub Accountlar arası transfer yapmayı sağlar. Master API KEY ve SECRET kullanır.
    public function sub_account_transfer( array $data ) { return $this->privateRequest('sapi/v1/broker/transfer', $data, 'POST'); }

}
