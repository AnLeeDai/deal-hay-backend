<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopCoupon;
use App\Models\ShopProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $coupon = $this->route('shopCoupon');

        return $this->user()?->role === UserRoleEnum::SHOP_OWNER
            && (bool) $this->user()?->is_active
            && $coupon instanceof ShopCoupon
            && $coupon->shop_owner_id === $this->user()?->getKey();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maximumDiscountValue = $this->input('discount_type') === 'percent' ? 100 : 999999999;

        return [
            'code' => ['prohibited'],
            'product_ids' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'product_ids.*' => ['required', 'uuid', 'distinct', Rule::exists(ShopProduct::class, 'id')->where('shop_owner_id', $this->user()?->getKey())],
            'name' => ['required', 'string', 'max:120'],
            'discount_type' => ['required', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['required', 'integer', 'min:1', 'max:'.$maximumDiscountValue],
            'minimum_order_amount' => ['required', 'integer', 'min:0', 'max:999999999'],
            'maximum_discount_amount' => ['nullable', 'integer', 'min:1', 'max:999999999'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
