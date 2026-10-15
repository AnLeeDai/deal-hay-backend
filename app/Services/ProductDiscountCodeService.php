<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Models\ProductDiscountCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductDiscountCodeService
{
    public function save(User $user, ProductDiscountCode $code): ProductDiscountCode
    {
        return DB::transaction(function () use ($user, $code): ProductDiscountCode {
            $code = ProductDiscountCode::query()->lockForUpdate()->findOrFail($code->getKey());
            abort_unless($code->user_id === $user->getKey(), 404);

            if ($code->used_at !== null || $code->shop_order_id !== null || ! $code->expires_at->isFuture()) {
                throw ValidationException::withMessages(['discount_code' => 'Mã giảm giá đã hết hạn hoặc đã được sử dụng.']);
            }

            if ($code->saved_at === null) {
                $code->update(['saved_at' => now()]);
            }

            return $code;
        }, attempts: 5);
    }

    /** @return array<string, mixed> */
    public function payload(ProductDiscountCode $code): array
    {
        $code->loadMissing('product.shopOwner');
        $product = $code->product;
        $productPayload = $product->toArray();
        $shop = $product->shopOwner;

        return [
            'id' => (string) $code->getKey(),
            'code' => $code->code,
            'original_price' => $code->original_price,
            'sale_price' => $code->sale_price,
            'reward_type' => $code->reward_type ?? 'legacy_ratio',
            'reward_value' => $code->reward_value,
            'fixed_amount' => $code->reward_type === 'fixed' ? $code->reward_value : null,
            'discount_percent' => $code->reward_type === 'fixed' ? 0 : round((1 - $code->sale_price / $code->original_price) * 100, 2),
            'expires_at' => $code->expires_at->toISOString(),
            'saved_at' => $code->saved_at?->toISOString(),
            'used_at' => $code->used_at?->toISOString(),
            'is_available' => $code->saved_at !== null && $code->used_at === null
                && $code->shop_order_id === null && $code->expires_at->isFuture()
                && (bool) $product->is_active && (bool) $shop->is_active
                && $shop->role === UserRoleEnum::SHOP_OWNER,
            'product' => [
                'id' => (string) $product->getKey(),
                'name' => $product->name,
                'slug' => $product->slug,
                'image_url' => $productPayload['image_url'],
                'shop' => ['code' => $shop->user_code, 'name' => $shop->shop_name ?: $shop->full_name],
            ],
        ];
    }
}
