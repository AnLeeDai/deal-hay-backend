<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopProductReview;
use App\Models\User;

class ReplyToShopProductReview
{
    public function handle(User $shopOwner, ShopProductReview $review, string $reply): void
    {
        abort_unless(
            $shopOwner->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shopOwner->is_active
            && $review->product()
                ->whereBelongsTo($shopOwner, 'shopOwner')
                ->exists(),
            403,
        );

        $review->update(['shop_reply' => $reply, 'replied_at' => now()]);
    }
}
