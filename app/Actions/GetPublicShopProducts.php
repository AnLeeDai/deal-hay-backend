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
                'old_price',
                'video_url',
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
                    ->where('is_active', true)
                    ->reorder()
                    ->orderByRaw('CASE WHEN is_default = ? AND stock > 0 THEN 0 WHEN stock > 0 THEN 1 WHEN is_default = ? THEN 2 ELSE 3 END', [true, true])
                    ->orderBy('position')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->limit(1),
            ])
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ShopProduct $product): array => PublicShopProductPayload::forShop($product, includeVariants: false));
    }
}
