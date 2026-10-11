<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopCoupon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveShopCoupon
{
    /** @param array<string, mixed> $data */
    public function handle(User $shopOwner, array $data, ?ShopCoupon $coupon = null): ShopCoupon
    {
        abort_unless(
            $shopOwner->role === UserRoleEnum::SHOP_OWNER && (bool) $shopOwner->is_active,
            403,
        );

        if ($coupon === null) {
            return $shopOwner->shopCoupons()->create($data);
        }

        return DB::transaction(function () use ($coupon, $data, $shopOwner): ShopCoupon {
            $lockedCoupon = ShopCoupon::query()
                ->lockForUpdate()
                ->findOrFail($coupon->getKey());

            abort_unless($lockedCoupon->shop_owner_id === $shopOwner->getKey(), 403);
            $lockedCoupon->update($data);

            return $lockedCoupon;
        }, attempts: 5);
    }
}
