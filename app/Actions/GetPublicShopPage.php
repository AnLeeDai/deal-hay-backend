<?php

namespace App\Actions;

use App\Models\ProductCategory;
use App\Models\User;
use App\Services\PublicShopService;
use Illuminate\Support\Collection;

class GetPublicShopPage
{
    public function __construct(
        private readonly GetPublicShopProducts $getProducts,
        private readonly GetShopEngagementMetrics $engagementMetrics,
        private readonly PublicShopService $publicShops,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $shop, ?User $viewer): array
    {
        $this->publicShops->ensureIsPublic($shop);
        $products = $this->getProducts->handle($shop, 60);

        return [
            'shop' => $this->publicShops->payload(
                $shop,
                $this->engagementMetrics->handle($shop, $viewer),
            ),
            'products' => $products,
            'categories' => $this->categories($shop, $products),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @return list<string>
     */
    private function categories(User $shop, Collection $products): array
    {
        $categoryNames = $products->pluck('category')->unique();
        $assignedCategories = $shop->productCategories()
            ->where('is_active', true)
            ->whereIn('name', $categoryNames)
            ->orderByPivot('display_order')
            ->get();

        return $assignedCategories
            ->concat(ProductCategory::query()
                ->where('is_active', true)
                ->whereIn('name', $categoryNames)
                ->whereKeyNot($assignedCategories->modelKeys())
                ->orderBy('name')
                ->get())
            ->pluck('name')
            ->all();
    }
}
