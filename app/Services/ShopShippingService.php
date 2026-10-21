<?php

namespace App\Services;

use App\Models\ShopShippingSetting;
use App\Models\User;

class ShopShippingService
{
    public const int HANOI_PROVINCE_CODE = 1;

    /** @return array{enabled:bool,base_fee:int,ward_rates:list<array{ward_code:int,fee:int}>} */
    public function settings(User $shop): array
    {
        $settings = ShopShippingSetting::query()->where('shop_owner_id', $shop->getKey())->first();

        return [
            'enabled' => $settings?->enabled ?? false,
            'base_fee' => $settings?->base_fee ?? 0,
            'ward_rates' => $settings?->ward_rates ?? [],
        ];
    }

    /** @return array{eligible:bool,configured:bool,fee:int|null,province_code:int,ward_code:int,reason:string|null} */
    public function quote(User $shop, int $provinceCode, int $wardCode): array
    {
        $settings = $this->settings($shop);
        $eligible = $provinceCode === self::HANOI_PROVINCE_CODE;
        $configured = $settings['enabled'];
        $fee = null;

        if ($eligible && $configured) {
            $fee = $settings['base_fee'];
            foreach ($settings['ward_rates'] as $wardRate) {
                if ($wardRate['ward_code'] === $wardCode) {
                    $fee = $wardRate['fee'];
                    break;
                }
            }
        }

        return [
            'eligible' => $eligible,
            'configured' => $configured,
            'fee' => $fee,
            'province_code' => $provinceCode,
            'ward_code' => $wardCode,
            'reason' => ! $eligible ? 'outside_hanoi' : (! $configured ? 'not_configured' : null),
        ];
    }
}
