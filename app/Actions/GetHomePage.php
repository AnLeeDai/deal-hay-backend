<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\PublicShopService;
use App\Support\PublicShopProductPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GetHomePage
{
    public function __construct(private readonly PublicShopService $publicShops) {}

    /** @return array<string, mixed> */
    public function handle(string $selectedCategory): array
    {
        $categories = $this->categories();

        if ($selectedCategory !== '' && ! $categories->contains($selectedCategory)) {
            $selectedCategory = '';
        }

        return [
            'featuredShops' => $this->featuredShops(),
            'products' => $this->products($selectedCategory),
            'categories' => $categories,
            'filters' => ['category' => $selectedCategory],
        ];
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    private function products(string $category): LengthAwarePaginator
    {
        return $this->publicProductsQuery()
            ->select([
                'id',
                'shop_owner_id',
                'product_category_id',
                'slug',
                'name',
                'price',
                'old_price',
                'stock',
                'has_variants',
                'label',
                'image_url',
            ])
            ->with([
                'productCategory:id,name',
                'shopOwner:id,user_code,shop_name,shop_avatar_url',
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
            ->when($category !== '', fn (Builder $query) => $query
                ->whereHas('productCategory', fn (Builder $query) => $query->where('name', $category)))
            ->latest()
            ->paginate(18)
            ->withQueryString()
            ->through(fn (ShopProduct $product): array => PublicShopProductPayload::forHome($product));
    }

    /**
     * @return Collection<int, array{code: string, name: string, avatar_url: string|null, address: string, products_count: int}>
     */
    private function featuredShops(): Collection
    {
        return User::query()
            ->select(['id', 'user_code', 'shop_name', 'shop_avatar_url', 'shop_province', 'shop_ward'])
            ->where('role', UserRoleEnum::SHOP_OWNER)
            ->where('is_active', true)
            ->whereHas('shopProducts', fn (Builder $query) => $query->where('is_active', true))
            ->withCount(['shopProducts as products_count' => fn (Builder $query) => $query->where('is_active', true)])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (User $shop): array => $this->publicShops->featuredPayload($shop));
    }

    /** @return Collection<int, string> */
    private function categories(): Collection
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereHas('shopOwner', fn (Builder $query) => $query
                    ->where('role', UserRoleEnum::SHOP_OWNER)
                    ->where('is_active', true)))
            ->orderBy('name')
            ->pluck('name');
    }

    private function publicProductsQuery(): Builder
    {
        return ShopProduct::query()
            ->where('is_active', true)
            ->whereHas('shopOwner', fn (Builder $query) => $query
                ->where('role', UserRoleEnum::SHOP_OWNER)
                ->where('is_active', true));
    }
}
