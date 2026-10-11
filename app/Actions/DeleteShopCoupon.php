<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopCoupon;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteShopCoupon
{
    public function handle(User $shopOwner, ShopCoupon $coupon): bool
    {
        abort_unless(
            $shopOwner->role === UserRoleEnum::SHOP_OWNER && (bool) $shopOwner->is_active,
            403,
        );

        return DB::transaction(function () use ($coupon, $shopOwner): bool {
            $lockedCoupon = ShopCoupon::query()
                ->lockForUpdate()
                ->findOrFail($coupon->getKey());

            abort_unless($lockedCoupon->shop_owner_id === $shopOwner->getKey(), 403);

            if ($lockedCoupon->orders()->exists()) {
                return false;
            }

            $lockedCoupon->delete();

            return true;
        }, attempts: 5);
    }
}
