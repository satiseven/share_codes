<?php

namespace App\Helpers\Binance;

use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Setting\SettingHelper;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BinanceApiBase
{

    protected $api_key;             // API  
    protected $api_secret;          // API secret
    protected $api_url;             // API base URL
    protected $recvWindow;          // API receiving window
    protected $synced = FALSE;
    protected $response = NULL;
    protected $no_time_needed = [
        'api/v1/system/status',
        'api/v3/time',
        'api/v3/exchangeInfo',
        'api/v3/trades',
        'api/v3/avgPrice',
        'api/v3/ticker/24hr',
        'api/v3/ticker/price',

        /// Web Socket
        'api/v3/userDataStream',
    ];

    /**
     * Constructor for BinanceAPI.
     *
     * @param string $key     API key
     * @param string $secret  API secret
     * @param string $api_url API base URL (see config for example)
     * @param int    $timing  Binance API timing setting (default 10000)
     */
    public function __construct( $api_key = NULL, $api_secret = NULL, $timing = 10000 )
    {
        //$this->api_key    = ( ! empty($api_key) ) ? $api_key : config('binance-api.auth.key');
        $this->api_key    = ( ! empty($api_key) ) ? $api_key : SettingHelper::app()->get(SettingKeysEnum::BINANCE_API_KEY);
        $this->api_secret = ( ! empty($api_secret) ) ? $api_secret : SettingHelper::app()->get(SettingKeysEnum::BINANCE_API_SECRET);
        $this->api_url    = SettingHelper::app()->get(SettingKeysEnum::BINANCE_API_URL) ?? config('binance-api.url');
        $this->recvWindow = ( ! empty($timing) ) ? $timing : config('binance-api.settings.timing');
    }

    /**
     * API Key and Secret Key setter function.
     * It's required for USER_DATA endpoints.
     * https://binance-docs.github.io/apidocs/spot/en/#endpoint-security-type.
     *
     * @param string $key    API Key
     * @param string $secret API Secret
     */
    public function setAPI( $api_key, $api_secret )
    {
        $this->api_key    = $api_key;
        $this->api_secret = $api_secret;
    }

    /**
     * Make public requests (Security Type: NONE).
     *
     * @param string $url    URL Endpoint
     * @param array  $params Required and optional parameters
     * @param string $method GET, POST, PUT, DELETE
     *
     * @return mixed
     * @throws \Exception
     *
     */
    protected function publicRequest( $url, $params = [], $method = 'GET' )
    {
        // Build the POST data string
        if ( ! in_array($url, $this->no_time_needed) ) {
            $params['timestamp']  = $this->milliseconds();
            $params['recvWindow'] = $this->recvWindow;
        }

        $url = "$this->api_url$url";

        // Adding parameters to the url.
        $url = "$url?" . http_build_query($params);

        return $this->sendApiRequest($url, $method);
    }

    /**
     * Make public requests (Security Type: USER_DATA).
     *
     * @param string $url    URL Endpoint
     * @param array  $params Required and optional parameters.
     */
    protected function privateRequest( $url, $params = [], $method = 'GET' )
    {
        // Build the POST data string
        if ( ! in_array($url, $this->no_time_needed) ) {
            $params['timestamp']  = $this->milliseconds();
            $params['recvWindow'] = $this->recvWindow;
        }

        // Build the query to pass through.
        $query = http_build_query($params);

        // Set API key and sign the message
        $signature = hash_hmac('sha256', $query, $this->api_secret);

        $url = "$this->api_url$url?$query&signature=$signature";

        return $this->sendApiRequest($url, $method);
    }

    /**
     * Send request to Wazirx API for Public or Private Requests.
     *
     * @param string $url    URL Endpoint with Query & Signature
     * @param string $method GET, POST, PUT, DELETE
     *
     * @return mixed
     * @throws \Exception
     *
     */
    protected function sendApiRequest( $url, $method )
    {
        try {
            $response = Http::withHeaders([
                'X-MBX-APIKEY' => $this->api_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);

            if ( $method === 'POST' ) {
                $response->withBody('', '');
                $response = $response->post($url);
            } else if ( $method === 'GET' ) {
                $response = $response->get($url);
            }
        } catch ( ConnectionException $e ) {
            return [
                'code'    => $e->getCode(),
                'error'   => 'Host Not Found',
                'message' => 'Could not resolve host: ' . $this->api_url,
            ];
        } catch ( Exception $e ) {
            return [
                'code'    => $e->getCode(),
                'error'   => 'cUrl Error',
                'message' => $e->getMessage(),
            ];
        }

        // If response if Ok. Return collection.
        if ( $response->ok() ) {
            return $response->collect();
        } else {
            dd([
                'BinancApiBase.php',
                json_decode($response, TRUE),
            ]);
        }
    }

    /**
     * Get the milliseconds from the system clock.
     *
     * @return int
     */
    private function milliseconds()
    {
        [ $msec, $sec ] = explode(' ', microtime());

        return $sec . substr($msec, 2, 3);
    }
}