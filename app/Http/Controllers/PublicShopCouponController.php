<?php

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Models\ShopCoupon;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicShopCouponController extends Controller
{
    public function index(Request $request, User $shop): JsonResponse
    {
        abort_unless($shop->role === UserRoleEnum::SHOP_OWNER && $shop->is_active, 404);
        $data = $request->validate(['product_id' => ['nullable', 'uuid']]);
        $coupons = ShopCoupon::query()->where('shop_owner_id', $shop->getKey())
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->whereHas('products', function ($query) use ($shop, $data): void {
                $query->where('shop_owner_id', $shop->getKey())->where('is_active', true);
                if (isset($data['product_id'])) {
                    $query->whereKey($data['product_id']);
                }
            })
            ->with(['products' => fn ($query) => $query->where('shop_owner_id', $shop->getKey())
                ->where('is_active', true)->select('shop_products.id', 'name', 'slug')])
            ->latest()->get();

        return response()->json(['coupons' => $coupons->map(fn (ShopCoupon $coupon): array => [
            'id' => $coupon->getKey(),
            'code' => $coupon->code,
            'name' => $coupon->name,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'minimum_order_amount' => $coupon->minimum_order_amount,
            'maximum_discount_amount' => $coupon->maximum_discount_amount,
            'remaining_uses' => $coupon->usage_limit === null ? null : max(0, $coupon->usage_limit - $coupon->used_count),
            'starts_at' => $coupon->starts_at?->toISOString(),
            'ends_at' => $coupon->ends_at?->toISOString(),
            'products' => $coupon->products->map(fn ($product): array => [
                'id' => $product->getKey(), 'name' => $product->name, 'slug' => $product->slug,
            ])->all(),
        ])->all()]);
    }
}
