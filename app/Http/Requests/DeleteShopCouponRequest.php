<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopCoupon;
use Illuminate\Foundation\Http\FormRequest;

class DeleteShopCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        $coupon = $this->route('shopCoupon');

        return $this->user()?->role === UserRoleEnum::SHOP_OWNER
            && (bool) $this->user()?->is_active
            && $coupon instanceof ShopCoupon
            && $coupon->shop_owner_id === $this->user()?->getKey();
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
