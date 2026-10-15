<?php

namespace App\Actions;

use App\Models\ShopProduct;
use App\Models\User;
use App\Services\PublicShopService;
use App\Support\PublicShopProductPayload;

class GetPublicProductPage
{
    public function __construct(
        private readonly GetPublicShopProducts $getProducts,
        private readonly GetShopEngagementMetrics $engagementMetrics,
        private readonly GetShopProductReviews $productReviews,
        private readonly PublicShopService $publicShops,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $shop, ShopProduct $product, ?User $viewer): array
    {
        $this->publicShops->ensureIsPublic($shop);
        abort_unless($product->is_active, 404);
        $product->load([
            'images',
            'options.values',
            'productCategory:id,name',
            'variants' => fn ($query) => $query
                ->where('is_active', true)
                ->with('optionValues.option'),
        ]);
        $reviewData = $this->productReviews->productPage($product, $viewer);

        return [
            'shop' => $this->publicShops->payload(
                $shop,
                $this->engagementMetrics->handle($shop, $viewer),
            ),
            'product' => PublicShopProductPayload::forShop($product),
            'relatedProducts' => $this->getProducts->handle($shop, 6, $product),
            'reviews' => $reviewData['reviews'],
            'reviewSummary' => $reviewData['summary'],
            'viewerReview' => $reviewData['viewer_review'],
            'canReview' => $reviewData['can_review'],
        ];
    }
}
