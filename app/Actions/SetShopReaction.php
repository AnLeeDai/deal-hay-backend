<?php

namespace App\Actions;

use App\Enums\ShopReactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\ShopReaction;
use App\Models\User;

class SetShopReaction
{
    public function handle(
        User $customer,
        User $shopOwner,
        ?ShopReactionTypeEnum $reaction,
    ): void {
        abort_unless(
            in_array($customer->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER, UserRoleEnum::ADMINISTRATOR], true)
            && (bool) $customer->is_active
            && $shopOwner->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shopOwner->is_active
            && $customer->getKey() !== $shopOwner->getKey(),
            403,
        );

        if ($reaction !== null) {
            ShopReaction::query()->updateOrCreate(
                ['customer_id' => $customer->getKey(), 'shop_owner_id' => $shopOwner->getKey()],
                ['reaction' => $reaction->value],
            );

            return;
        }

        ShopReaction::query()
            ->whereBelongsTo($customer, 'customer')
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->delete();
    }
}
