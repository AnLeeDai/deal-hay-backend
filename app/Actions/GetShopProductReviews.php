<?php

namespace App\Actions;

use App\Enums\ShopReactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;

class GetShopProductReviews
{
    /** @return array{reviews: list<array<string, mixed>>, summary: array<string, int>, viewer_review: array<string, mixed>|null} */
    public function productPage(ShopProduct $product, ?User $viewer): array
    {
        $reviews = ShopProductReview::query()
            ->whereBelongsTo($product, 'product')
            ->with('customer:id,full_name,avatar_url')
            ->latest()
            ->limit(50)
            ->get();
        $reviewCounts = ShopProductReview::query()->whereBelongsTo($product, 'product');
        $viewerCanReview = $viewer !== null
            && in_array($viewer->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER], true)
            && (bool) $viewer->is_active
            && $viewer->getKey() !== $product->shop_owner_id;
        $viewerReview = $viewerCanReview
            ? ShopProductReview::query()
                ->whereBelongsTo($product, 'product')
                ->whereBelongsTo($viewer, 'customer')
                ->with('customer:id,full_name,avatar_url')
                ->first()
            : null;

        return [
            'reviews' => $reviews->map(fn (ShopProductReview $review): array => $this->payload($review))->all(),
            'summary' => [
                'total_count' => (clone $reviewCounts)->count(),
                'likes_count' => (clone $reviewCounts)->where('reaction', ShopReactionTypeEnum::Like)->count(),
                'dislikes_count' => (clone $reviewCounts)->where('reaction', ShopReactionTypeEnum::Dislike)->count(),
            ],
            'viewer_review' => $viewerReview instanceof ShopProductReview
                ? $this->payload($viewerReview)
                : null,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function forShop(User $shop, int $limit = 100): array
    {
        return ShopProductReview::query()
            ->whereHas('product', fn ($query) => $query->where('shop_owner_id', $shop->getKey()))
            ->with([
                'customer:id,full_name,avatar_url',
                'product:id,shop_owner_id,slug,name,image_url',
            ])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ShopProductReview $review): array => $this->payload($review, true))
            ->all();
    }

    /** @return array<string, mixed> */
    private function payload(ShopProductReview $review, bool $includeProduct = false): array
    {
        $reviewPayload = $review->toArray();
        $customerPayload = $review->customer->toArray();
        $payload = [
            'id' => (string) $review->getKey(),
            'reaction' => $review->reaction->value,
            'content' => (string) $review->content,
            'image_url' => $reviewPayload['image_url'] ?? null,
            'shop_reply' => $review->shop_reply,
            'replied_at' => $review->replied_at?->toISOString(),
            'created_at' => $review->created_at?->toISOString(),
            'updated_at' => $review->updated_at?->toISOString(),
            'customer' => [
                'id' => (string) $review->customer->getKey(),
                'name' => (string) $review->customer->full_name,
                'avatar_url' => $customerPayload['avatar_url'] ?? null,
            ],
        ];

        if ($includeProduct) {
            $productPayload = $review->product->toArray();
            $payload['product'] = [
                'id' => (string) $review->product->getKey(),
                'slug' => (string) $review->product->slug,
                'name' => (string) $review->product->name,
                'image_url' => $productPayload['image_url'] ?? null,
            ];
        }

        return $payload;
    }
}
