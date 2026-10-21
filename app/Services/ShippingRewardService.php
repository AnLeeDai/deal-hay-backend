<?php

namespace App\Services;

use App\Enums\UserRoleEnum;
use App\Models\ShippingDiscountCode;
use App\Models\ShippingRewardView;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShippingRewardService
{
    public function __construct(private readonly ShopShippingService $shipping) {}

    public function assertPublicProduct(User $shop, ShopProduct $product): void
    {
        abort_unless($shop->role === UserRoleEnum::SHOP_OWNER && $shop->is_active
            && $product->shop_owner_id === $shop->getKey() && $product->is_active, 404);
    }

    /** @return array{available:bool,unavailable_reason:string|null} */
    public function offer(User $shop, ShopProduct $product): array
    {
        $this->assertPublicProduct($shop, $product);
        $reason = ! config('shipping_rewards.enabled') ? 'promotion_disabled'
            : (! $this->shipping->settings($shop)['enabled'] ? 'shipping_not_configured' : null);

        return ['available' => $reason === null, 'unavailable_reason' => $reason];
    }

    public function start(User $customer, User $shop, ShopProduct $product): ShippingRewardView
    {
        return DB::transaction(function () use ($customer, $shop, $product): ShippingRewardView {
            [$shop, $product] = $this->lockProduct($shop, $product);
            $this->assertAvailable($shop, $product);

            return ShippingRewardView::query()->create([
                'user_id' => $customer->getKey(),
                'shop_product_id' => $product->getKey(),
                'expires_at' => now()->addMinutes(max(1, min(1440, (int) config('shipping_rewards.session_minutes', 15)))),
            ]);
        }, attempts: 5);
    }

    public function redeem(User $customer, User $shop, ShopProduct $product, ShippingRewardView $view): ShippingDiscountCode
    {
        return DB::transaction(function () use ($customer, $shop, $product, $view): ShippingDiscountCode {
            [$shop, $product] = $this->lockProduct($shop, $product);
            $this->assertPublicProduct($shop, $product);
            $view = ShippingRewardView::query()->lockForUpdate()->findOrFail($view->getKey());
            abort_unless($view->user_id === $customer->getKey() && $view->shop_product_id === $product->getKey(), 404);

            if ($view->shipping_discount_code_id !== null) {
                return ShippingDiscountCode::query()->lockForUpdate()->findOrFail($view->shipping_discount_code_id);
            }

            $this->assertAvailable($shop, $product);
            if (! $view->expires_at->isFuture()) {
                throw ValidationException::withMessages(['shipping_offer' => 'Phiên nhận ưu đãi đã hết hạn. Vui lòng mở lại ưu đãi vận chuyển.']);
            }

            $code = $this->currentCode($customer, $product, lock: true)
                ?? ShippingDiscountCode::query()->create([
                    'user_id' => $customer->getKey(),
                    'shop_product_id' => $product->getKey(),
                    'code' => Str::upper(Str::random(32)),
                    'expires_at' => now()->addDays(max(1, min(365, (int) config('shipping_rewards.coupon_days', 7)))),
                ]);
            $view->update(['shipping_discount_code_id' => $code->getKey(), 'redeemed_at' => now()]);

            return $code;
        }, attempts: 5);
    }

    public function currentCode(User $customer, ShopProduct $product, bool $lock = false): ?ShippingDiscountCode
    {
        return ShippingDiscountCode::query()->where('user_id', $customer->getKey())
            ->where('shop_product_id', $product->getKey())->whereNull('used_at')->whereNull('shop_order_id')
            ->where('expires_at', '>', now())->when($lock, fn ($query) => $query->lockForUpdate())->latest()->first();
    }

    /** @return array{id:string,code:string,expires_at:string,used_at:string|null,is_available:bool,product_id:string,discount_percent:int} */
    public function payload(ShippingDiscountCode $code): array
    {
        return [
            'id' => (string) $code->getKey(),
            'code' => $code->code,
            'expires_at' => $code->expires_at->toISOString(),
            'used_at' => $code->used_at?->toISOString(),
            'is_available' => $code->isAvailable(),
            'product_id' => (string) $code->shop_product_id,
            'discount_percent' => 100,
        ];
    }

    /** @return array{User,ShopProduct} */
    private function lockProduct(User $shop, ShopProduct $product): array
    {
        $shop = User::query()->lockForUpdate()->findOrFail($shop->getKey());
        $product = ShopProduct::query()->where('shop_owner_id', $shop->getKey())->lockForUpdate()->findOrFail($product->getKey());

        return [$shop, $product];
    }

    private function assertAvailable(User $shop, ShopProduct $product): void
    {
        if (! $this->offer($shop, $product)['available']) {
            throw ValidationException::withMessages(['shipping_offer' => 'Ưu đãi miễn phí vận chuyển hiện chưa khả dụng.']);
        }
    }
}
