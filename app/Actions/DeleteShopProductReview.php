<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Support\Facades\DB;

class DeleteShopProductReview
{
    public function __construct(private readonly MediaClient $cleanup) {}

    public function handle(User $customer, ShopProduct $product): void
    {
        abort_unless(
            in_array($customer->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER], true)
            && (bool) $customer->is_active
            && (bool) $product->is_active
            && $customer->getKey() !== $product->shop_owner_id,
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
