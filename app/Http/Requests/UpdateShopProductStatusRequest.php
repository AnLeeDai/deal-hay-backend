<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShopProductStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('shopProduct');

        return $this->user()?->role === UserRoleEnum::SHOP_OWNER
            && $product instanceof ShopProduct
            && $product->shop_owner_id === $this->user()?->getKey();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
