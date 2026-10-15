<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\User;

class ShopProductReviewEligibility
{
    public function canManageOwnReview(?User $viewer, ShopProduct $product): bool
    {
        return $viewer !== null
            && in_array($viewer->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER, UserRoleEnum::ADMINISTRATOR], true)
            && (bool) $viewer->is_active
            && (bool) $product->is_active
            && $viewer->getKey() !== $product->shop_owner_id
            && $product->shopOwner()
                ->where('role', UserRoleEnum::SHOP_OWNER)
                ->where('is_active', true)
                ->exists();
    }

    public function canReview(?User $viewer, ShopProduct $product): bool
    {
        if (! $this->canManageOwnReview($viewer, $product)) {
            return false;
        }

        return ShopOrder::query()
            ->where('customer_id', $viewer->getKey())
            ->where('shop_owner_id', $product->shop_owner_id)
            ->whereIn('status', ['pending', 'confirmed', 'processing', 'shipping', 'completed'])
            ->whereNull('cancelled_at')
            ->whereHas('items', fn ($query) => $query
                ->where('shop_product_id', $product->getKey())
                ->where('quantity', '>', 0))
            ->exists();
    }
}
