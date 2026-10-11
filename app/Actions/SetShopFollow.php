<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopFollow;
use App\Models\User;

class SetShopFollow
{
    public function handle(User $follower, User $shopOwner, bool $following): void
    {
        abort_unless(
            in_array($follower->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER], true)
            && (bool) $follower->is_active
            && $shopOwner->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shopOwner->is_active
            && $follower->getKey() !== $shopOwner->getKey(),
            403,
        );

        if ($following) {
            ShopFollow::query()->firstOrCreate([
                'follower_id' => $follower->getKey(),
                'shop_owner_id' => $shopOwner->getKey(),
            ]);

            return;
        }

        ShopFollow::query()
            ->whereBelongsTo($follower, 'follower')
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->delete();
    }
}
