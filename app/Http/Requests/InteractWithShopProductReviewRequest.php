<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InteractWithShopProductReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $shop = $this->route('shop');
        $product = $this->route('shopProduct');

        return $user !== null
            && in_array($user->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER, UserRoleEnum::ADMINISTRATOR], true)
            && (bool) $user->is_active
            && $shop instanceof User
            && $shop->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shop->is_active
            && $product instanceof ShopProduct
            && (bool) $product->is_active
            && $product->shop_owner_id === $shop->getKey()
            && $user->getKey() !== $shop->getKey();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
