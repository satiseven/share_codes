<?php

namespace App\Http\Livewire\Front;

use App\Enums\Order\OrderAddressEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Setting\SettingKeysEnum;
use App\Helpers\Binance\BinanceService;
use App\Helpers\Binance\CalculatorHelper;
use App\Helpers\Petite\CryptHelper;
use App\Helpers\Setting\SettingHelper;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;
use function auth;
use function dd;
use function request;
use function route;
use function throw_unless;
use function view;

class PersonalInfo extends Component
{
    public $order;
    public $different_address;
    public $invoice_address;
    public $shipping_address;
    public $first_name;
    public $last_name;
    public $company_name;
    public $phone;
    public $notes;

    public function mount( $order_id )
    {
        $this->order = request('order');
        Gate::authorize('view', $this->order);
    }

    public function rules()
    {
        $inceice_address  = 'invoice_address.';
        $shipping_address = 'shipping_address.';
        $rules            = [
            'invoice_address'                                          => [ 'required', 'array' ],
            Order::FIRST_NAME                                          => [ 'required', 'string', 'max:50' ],
            Order::LAST_NAME                                           => [ 'required', 'string', 'max:50' ],
            Order::COMPANY_NAME                                        => [ 'nullable', 'string', 'max:200' ],
            Order::PHONE                                               => [ 'required', 'string', 'max:50' ], // TODO : Phone yapıldıktan sonra required yapılacak. required
            $inceice_address . OrderAddressEnum::COMPANY_NAME          => [ 'nullable', 'string', 'max:200' ],
            $inceice_address . OrderAddressEnum::COUNTRY               => [ 'required', 'string', 'max:50' ],
            $inceice_address . OrderAddressEnum::STREET_ADDRESS        => [ 'required', 'string', 'max:300' ],
            $inceice_address . OrderAddressEnum::STREET_ADDRESS_DETAIL => [ 'nullable', 'string', 'max:300' ],
            $inceice_address . OrderAddressEnum::CITY                  => [ 'required', 'string', 'max:50' ],
            $inceice_address . OrderAddressEnum::STATE                 => [ 'nullable', 'string', 'max:50' ],
            $inceice_address . OrderAddressEnum::ZIP_CODE              => [ 'required', 'string', 'max:20' ],
            Order::NOTES                                               => [ 'nullable', 'string', 'max:1000' ],
        ];

        if ( $this->different_address ) {
            $rules = array_merge($rules, [
                'shipping_address'                                          => [ 'required', 'array' ],
                $shipping_address . OrderAddressEnum::COUNTRY               => [ 'required', 'string', 'max:50' ],
                $shipping_address . OrderAddressEnum::STREET_ADDRESS        => [ 'required', 'string', 'max:300' ],
                $shipping_address . OrderAddressEnum::STREET_ADDRESS_DETAIL => [ 'nullable', 'string', 'max:300' ],
                $shipping_address . OrderAddressEnum::CITY                  => [ 'required', 'string', 'max:50' ],
                $shipping_address . OrderAddressEnum::STATE                 => [ 'nullable', 'string', 'max:50' ],
                $shipping_address . OrderAddressEnum::ZIP_CODE              => [ 'required', 'string', 'max:20' ],
            ]);
        }

        return $rules;
    }

    public function updated( $propertyName )
    {
        $this->validateOnly($propertyName);
    }

    public function updating( $name, $value )
    {
        $this->$name = trim(Str::title($value));
    }

    public function submit( BinanceService $binance_service )
    {
        Gate::authorize('update', $this->order);
        $this->validate();
        $order_id = $this->order[Order::ID];
        try {
            DB::beginTransaction();
            $order = Order::query()->find($order_id);
            $order->query()->find($order_id)->update([
                Order::NUMBER           => Order::createNumber(),
                Order::INVOICE_ADDRESS  => $this->invoice_address,
                Order::SHIPPING_ADDRESS => $this->different_address ? $this->shipping_address : $this->invoice_address,
                Order::FIRST_NAME       => $this->first_name,
                Order::LAST_NAME        => $this->last_name,
                Order::COMPANY_NAME     => $this->company_name,
                Order::PHONE            => $this->phone, // TODO : Phone yapıldıktan sonra açılacak.
                Order::NOTES            => $this->notes,
            ]);

            $selected_crypto  = $order[Order::CRYPTO];
            $selected_network = $order[Order::NETWORK];

            // Sub account oluşturulacak. ☺☺
            $sub_account       = $binance_service->subAccount(SettingHelper::app()->get(SettingKeysEnum::BINANCE_SUB_ACCOUNT_TAG));
            $sub_account_id    = $sub_account['subaccountId'];
            $sub_account_email = $sub_account['email'];

            // Sub account api key ve secret oluşturulacak. ☺☺
            $sub_account_api        = $binance_service->subAccountApi($sub_account_id);
            $sub_account_api_key    = $sub_account_api['apiKey'];
            $sub_account_api_secret = $sub_account_api['secretKey'];

            // Deposit address oluşturulacak. getDepositAddress
            $binance_service->setAPI($sub_account_api_key, $sub_account_api_secret);
            $deposit_address_infos = $binance_service->getDepositAddress($selected_crypto, $selected_network);
            $deposit_address       = $deposit_address_infos['address'];
            $deposit_url           = $deposit_address_infos['url'];


            // Payment kaydı oluşturulacak.
            $payment = Payment::query()->create([
                Payment::ORDER_ID               => $order_id,
                Payment::CRYPTO                 => $selected_crypto,
                Payment::CRYPTO_AMOUNT          => CalculatorHelper::convert_crypto($order, $selected_crypto),
                Payment::NETWORK                => $selected_network,
                Payment::SUB_ACCOUNT_ID         => $sub_account_id,
                Payment::SUB_ACCOUNT_EMAIL      => $sub_account_email,
                Payment::SUB_ACCOUNT_API_KEY    => $sub_account_api_key,
                Payment::SUB_ACCOUNT_API_SECRET => $sub_account_api_secret,
                Payment::DEPOSIT_ADDRESS        => $deposit_address,
                Payment::DEPOSIT_URL            => $deposit_url,
                Payment::EXPIRE_MINUTE          => SettingHelper::app()->get(SettingKeysEnum::PAYMENT_EXPIRED_MINUTE),
                Payment::AMOUNT                 => $order[Order::PRODUCT]['price'],
                Payment::CURRENCY               => $order[Order::PRODUCT]['currency'],
                Payment::STATUS                 => PaymentStatusEnum::WAITING,
            ]);

            // HTTP isteği yapılacak.

            $response = Http::withOptions([ 'verify' => FALSE ])
                ->withHeaders([ 'Accept' => 'application/json' ])
                ->get(route('payment_start', CryptHelper::encode_parameter($this->order[Order::ID])), [ 'token' => CryptHelper::encode_parameter(auth()->id()) ]);

            throw_unless($response->ok(), new \Exception('Payment not started.', 500));
            // Bir job oluşturulacak. Bu job her dakika ödeme geldi mi diye kontrol edecek. Bu kontrol sonucunda eğer ödeme gelirse bunu completed yapacak. Eğer belirtilen expire minutes içinde ödeme gelmez ise Payment status 'expired' olacak. Ve münkünse ödeme gelip gelmediği veya Expired olup olmadığı anlık olarak kullanıcının sayfasında yazılmalı.

            DB::commit();

            $this->redirect(route('payment_waiting', CryptHelper::encode_parameter($this->order[Order::ID])));
        } catch ( \Exception $exception ) {
            DB::rollBack();
            dd($exception);
        }
    }

    public function render()
    {
        return view('livewire.front.personal-info');
    }
}
