<?php

namespace App\Http\Livewire\Front;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Binance\MinAmountHelper;
use App\Helpers\General\GeneralHelper;
use App\Helpers\Petite\CryptHelper;
use App\Helpers\Setting\SettingHelper;
use App\Models\Order;
use App\Models\SupportedSite;
use App\Rules\SupportedSite\IsSupportedRule;
use Livewire\Component;
use Symfony\Component\HttpClient\HttpClient;
use function auth;
use function now;
use function view;

class TakeProductUrl extends Component
{
    public $url;

    //public $orders = [];
    //public $order;

    public function reset_url()
    {
        //$this->last_product_add();
        $this->url = $this->order = NULL;
    }

    public function rules()
    {
        return [
            'url' => [ 'required', 'url', new IsSupportedRule ],
        ];
    }

    public function updated( $propertyName )
    {
        $this->validateOnly($propertyName);
    }

    public function mount()
    {
        if ( auth()->check() ) {
            $this->orders = Order::query()
                ->where(Order::USER_ID, auth()->id())
                ->whereDate(Order::CREATED_AT, '<=', now()->addMinutes(30))
                ->whereTime(Order::CREATED_AT, '<=', now()->addMinutes(30))
                ->orderByDesc(Order::ID)
                ->get();
        }
    }

    public function search()
    {
        $this->validate();

        // Önceki aramayı eğer 30 dakikayı geçmediyse son 30 dakika listesine ekleme.
        // $this->last_product_add();

        $base_url = parse_url($this->url, PHP_URL_HOST);
        $site     = SupportedSite::query()->firstWhere(SupportedSite::BASE_URL, $base_url);

        $helper_class_namespace = $site[SupportedSite::HELPER_CLASS];

        $helper = new $helper_class_namespace(NULL);
        $url    = $helper->edited_link($this->url);

        $goutte_client = new \Goutte\Client(HttpClient::create([ 'timeout' => 5 ]));

        $is_data           = TRUE;
        $request_counter   = 50;
        $product_image_src = $product_title = $product_price = $product_currency = NULL;

        while ($is_data) {
            $crawler = $goutte_client->request('GET', $url);
            $helper  = new $helper_class_namespace($crawler);

            $product_image_src = $helper->get_product_image_src($site[SupportedSite::IMAGE_SELECTOR]);
            $product_title     = $helper->get_product_title($site[SupportedSite::TITLE_SELECTOR]);
            $product_price     = $helper->get_product_price($site[SupportedSite::PRICE_SELECTOR]);
            $product_currency  = $helper->get_product_currency($site[SupportedSite::CURRENCY_SELECTOR]);
            $is_data           = ! $product_title && ! $product_price;
            if ( ( --$request_counter ) <= 0 ) break;
        }

        if ( ! $is_data ) {

            // Min amount kontrolü yapılacak.
            $min_order_usd_amount = SettingHelper::app()->get(SettingKeysEnum::MIN_ORDER_USD_AMOUNT);
            if ( $product_price < ( $min_amount = MinAmountHelper::get($min_order_usd_amount, $product_currency) ) ) {
                $this->addError('url', "$min_order_usd_amount USD'den yüksek fiyatlı ürünler giriniz.");
                return;
            }

            $order = Order::query()->create([
                Order::SUPPORTED_SITE_ID => $site[SupportedSite::ID],
                Order::MAC_ADDRESS       => GeneralHelper::get_mac_address(),
                Order::USER_ID           => auth()->id(),
                Order::STATUS            => OrderStatusEnum::SEARCH,
                Order::LINK              => $url,
                Order::PRODUCT           => [
                    'image'    => $product_image_src,
                    'title'    => $product_title,
                    'price'    => $product_price,
                    'currency' => $product_currency,
                ],
                Order::INVOICE_ADDRESS   => [],
                Order::SHIPPING_ADDRESS  => [],
                Order::FIRST_NAME        => '',
                Order::LAST_NAME         => '',
                Order::PHONE             => '',
                Order::NOTES             => NULL,
            ]);

            $this->redirect(route('exchange_to_crypto', CryptHelper::encode_parameter($order[Order::ID])));
            //$this->order = $order;
            //$this->emit('view_product', $order);
        } else {
            $this->addError('url', "Ürün bilgileri alınamadı.");
        }
    }

    // Önceki aramayı eğer 30 dakikayı geçmediyse son 30 dakika listesine ekleme.
    // private function last_product_add()
    // {
    //     $order = $this->order;
    //     if ( $order && $order[Order::CREATED_AT] <= now()->addMinutes(30) ) {
    //         $this->orders = collect($this->orders)->prepend($order);
    //     }
    //
    //     $this->order = NULL;
    // }

    public function render()
    {
        return view('livewire.front.take-product-url');
    }
}
