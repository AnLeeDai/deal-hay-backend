<?php

namespace App\Actions;

use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;
use App\Services\MediaClient;
use App\Services\ShopProductReviewEligibility;
use Illuminate\Support\Facades\DB;

class DeleteShopProductReview
{
    public function __construct(
        private readonly MediaClient $cleanup,
        private readonly ShopProductReviewEligibility $eligibility,
    ) {}

    public function handle(User $customer, ShopProduct $product): void
    {
        abort_unless(
            $this->eligibility->canManageOwnReview($customer, $product),
            403,
        );

        $review = ShopProductReview::query()
            ->whereBelongsTo($customer, 'customer')
            ->whereBelongsTo($product, 'product')
            ->first();

        if ($review instanceof ShopProductReview) {
            $image = $review->getRawOriginal('image_url');
            DB::transaction(function () use ($review, $image): void {
                $review->delete();
                $this->cleanup->deleteLater([$image]);
            });
        }
    }
}
