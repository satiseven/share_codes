<?php

namespace App\Console\Commands;

use App\Helpers\Binance\BinanceService;
use App\Models\Coin;
use App\Models\Network;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GetCoinsInfo extends Command
{
    protected $signature = 'get:coin_infos';

    protected $description = 'Binance\'daki tüm coinlerin bilgilerini alır.';

    public function handle( BinanceService $binance_service )
    {
        try {
            $coins_infos = $binance_service->getUserCoinsInfo();
            DB::beginTransaction();
            foreach ($coins_infos as $coins_info) {
                $coin = Coin::query()->updateOrCreate(
                    [ Coin::COIN => $coins_info['coin'] ],
                    [
                        Coin::DEPOSIT_ALL_ENABLE  => $coins_info['depositAllEnable'],
                        Coin::WITHDRAW_ALL_ENABLE => $coins_info['withdrawAllEnable'],
                        Coin::NAME                => $coins_info['name'],
                        Coin::FREE                => $coins_info['free'],
                        Coin::LOCKED              => $coins_info['locked'],
                        Coin::FREEZE              => $coins_info['freeze'],
                        Coin::WITHDRAWING         => $coins_info['withdrawing'],
                        Coin::IPOING              => $coins_info['ipoing'],
                        Coin::IPOABLE             => $coins_info['ipoable'],
                        Coin::STORAGE             => $coins_info['storage'],
                        Coin::IS_LEGAL_MONEY      => $coins_info['isLegalMoney'],
                        Coin::TRANDING            => $coins_info['trading'],
                    ]
                );

                $networks = $coins_info['networkList'];
                foreach ($networks as $network) {
                    Network::query()->updateOrCreate(
                        [ Network::COIN_ID => $coin[Coin::ID], Network::NETWORK => $network[Network::NETWORK] ],
                        [
                            Network::IS_ACTIVE                 => $network['depositEnable'], // TODO: DEPLOY olurken kaldırılacak. Deposit özelliği olan tüm coinler TRUE oluyor şuanda.
                            Network::ADDRESS_REGEX             => $network['addressRegex'],
                            Network::COIN                      => $network['coin'],
                            Network::DEPOSIT_DESC              => $network['depositDesc'] ?? NULL,
                            Network::DEPOSIT_ENABLE            => $network['depositEnable'],
                            Network::IS_DEFAULT                => $network['isDefault'],
                            Network::MEMO_REGEX                => $network['memoRegex'],
                            Network::MIN_CONFIRM               => $network['minConfirm'],
                            Network::NAME                      => $network['name'],
                            Network::NETWORK                   => $network['network'],
                            Network::RESET_ADDRESS_STATUS      => $network['resetAddressStatus'],
                            Network::SPECIAL_TIPS              => $network['specialTips'] ?? NULL,
                            Network::UNLOCK_CONFIRM            => $network['unLockConfirm'],
                            Network::WITHDRAW_DESC             => $network['withdrawDesc'] ?? NULL,
                            Network::WITHDRAW_ENABLE           => $network['withdrawEnable'],
                            Network::WITHDRAW_FEE              => $network['withdrawFee'],
                            Network::WITHDRAW_INTEGER_MULTIPLE => $network['withdrawIntegerMultiple'],
                            Network::WITHDRAW_MAX              => $network['withdrawMax'],
                            Network::WITHDRAW_MIN              => $network['withdrawMin'],
                            Network::SAME_ADDRESS              => $network['sameAddress'],
                        ]
                    );
                }
            }

            Coin::query()->where(Coin::COIN, 'USDT')->update([ Coin::IS_ACTIVE => TRUE, Coin::PRECISION => 0 ]);
            Coin::query()->where(Coin::COIN, 'BTC')->orWhere(Coin::COIN, 'ETH')->update([ Coin::IS_ACTIVE => TRUE, Coin::PRECISION => 6 ]);

            DB::commit();
            dump('Coin infos updated successful!');
        } catch ( \Exception $exception ) {
            DB::rollBack();
            dd($exception);
        }
    }
}
