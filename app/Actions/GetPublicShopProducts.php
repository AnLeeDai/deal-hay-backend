<?php

namespace App\Actions;

use App\Models\ShopProduct;
use App\Models\User;
use App\Support\PublicShopProductPayload;
use Illuminate\Support\Collection;

class GetPublicShopProducts
{
    /** @return Collection<int, array<string, mixed>> */
    public function handle(User $shop, int $limit, ?ShopProduct $except = null): Collection
    {
        return ShopProduct::query()
            ->select([
                'id',
                'shop_owner_id',
                'product_category_id',
                'slug',
                'sku',
                'name',
                'description',
                'price',
                'stock',
                'has_variants',
                'label',
                'image_url',
            ])
            ->whereBelongsTo($shop, 'shopOwner')
            ->where('is_active', true)
            ->with([
                'productCategory:id,name',
                'variants' => fn ($query) => $query
                    ->select(['id', 'shop_product_id', 'price', 'stock', 'is_active', 'is_default', 'retired_at'])
                    ->where('is_active', true),
            ])
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ShopProduct $product): array => PublicShopProductPayload::forShop($product, includeVariants: false));
    }
}
