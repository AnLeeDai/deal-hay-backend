<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShopProductPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('shopProduct');

        return $this->user()?->role === UserRoleEnum::SHOP_OWNER && $product instanceof ShopProduct
            && $product->shop_owner_id === $this->user()->getKey();
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'integer', 'min:1000', 'max:999999999'],
            'old_price' => ['present', 'nullable', 'integer', 'gt:price', 'max:999999999'],
            'video_url' => ['present', 'nullable', 'url:https', 'max:2048'],
            'advertisement_video_url' => ['present', 'nullable', 'required_with:old_price', 'url:https', 'max:2048'],
            'advertisement_duration_seconds' => ['present', 'nullable', 'required_with:advertisement_video_url', 'integer', 'min:5', 'max:300'],
        ];
    }
}
