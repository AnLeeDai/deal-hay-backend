<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductDiscountCodeService
{
    public function issueFromCompletedView(User $user, ProductAdView $view): ProductDiscountCode
    {
        abort_unless($user->is_active && $view->user_id === $user->getKey(), 403);

        return DB::transaction(function () use ($user, $view): ProductDiscountCode {
            $product = ShopProduct::query()->findOrFail($view->shop_product_id);
            $shop = User::query()->lockForUpdate()->findOrFail($product->shop_owner_id);
            $product = ShopProduct::query()->lockForUpdate()->findOrFail($product->getKey());
            $view = ProductAdView::query()->lockForUpdate()->findOrFail($view->getKey());
            abort_unless($view->user_id === $user->getKey(), 403);
            $existing = ProductDiscountCode::query()->where('product_ad_view_id', $view->getKey())->first();

            if ($existing !== null) {
                return $existing;
            }

            $validReward = ($view->reward_type === 'fixed' && $view->reward_value > 0 && $view->reward_value <= 4294967295)
                || ($view->reward_type === 'percentage' && $view->reward_value > 0 && $view->reward_value <= 10000);
            if ($view->completed_at === null || $view->verified_at === null || ! filled($view->verification_reference)
                || ! filled($view->provider) || ! filled($view->campaign_id) || ! $validReward
                || ! $view->expires_at->isFuture() || $view->shop_order_id !== null
                || ! $product->is_active || ! $shop->is_active || $shop->role !== UserRoleEnum::SHOP_OWNER
                || $shop->getKey() === $user->getKey()) {
                throw ValidationException::withMessages(['advertisement_view_id' => 'Quảng cáo chưa đủ điều kiện để cấp mã giảm giá.']);
            }

            return ProductDiscountCode::query()->create([
                'user_id' => $user->getKey(),
                'shop_product_id' => $product->getKey(),
                'product_ad_view_id' => $view->getKey(),
                'code' => Str::upper(Str::random(32)),
                'original_price' => $view->reward_type === 'percentage' ? 10000 : 1,
                'sale_price' => $view->reward_type === 'percentage' ? 10000 - $view->reward_value : 1,
                'reward_type' => $view->reward_type,
                'reward_value' => $view->reward_value,
                'expires_at' => now()->addDays(max(1, min(365, (int) config('advertising.code_lifetime_days', 30)))),
            ]);
        }, attempts: 5);
    }

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
