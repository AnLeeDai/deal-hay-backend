<?php

namespace App\Actions;

use App\Models\ShopProduct;
use App\Models\User;

class UpdateShopProductStatus
{
    public function handle(User $shopOwner, ShopProduct $product, bool $isActive): void
    {
        abort_unless($product->shop_owner_id === $shopOwner->getKey(), 403);

        $product->update(['is_active' => $isActive]);
    }
}
