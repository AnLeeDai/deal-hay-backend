<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopCoupon;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveShopCoupon
{
    /** @param array<string, mixed> $data */
    public function handle(User $shopOwner, array $data, ?ShopCoupon $coupon = null): ShopCoupon
    {
        abort_unless(
            $shopOwner->role === UserRoleEnum::SHOP_OWNER && (bool) $shopOwner->is_active,
            403,
        );

        return DB::transaction(function () use ($coupon, $data, $shopOwner): ShopCoupon {
            $productIds = $data['product_ids'] ?? [];
            $ownedProducts = ShopProduct::query()->where('shop_owner_id', $shopOwner->getKey())
                ->whereKey($productIds)->orderBy('id')->lockForUpdate()->pluck('id');
            if ($ownedProducts->count() !== count($productIds) || $ownedProducts->isEmpty()) {
                throw ValidationException::withMessages(['product_ids' => 'Vui lòng chọn sản phẩm thuộc cửa hàng của bạn.']);
            }
            $attributes = Arr::except($data, ['code', 'product_ids']);
            if ($coupon === null) {
                $id = (string) Str::uuid();
                $lockedCoupon = new ShopCoupon($attributes);
                $lockedCoupon->id = $id;
                $lockedCoupon->code = 'SHOP'.strtoupper(str_replace('-', '', $id));
                $lockedCoupon->shop_owner_id = $shopOwner->getKey();
                $lockedCoupon->save();
            } else {
                $lockedCoupon = ShopCoupon::query()->lockForUpdate()->findOrFail($coupon->getKey());
                abort_unless($lockedCoupon->shop_owner_id === $shopOwner->getKey(), 403);
                $lockedCoupon->update($attributes);
            }

            $lockedCoupon->products()->sync($productIds);

            return $lockedCoupon->load('products:id,name,slug');
        }, attempts: 5);
    }
}
