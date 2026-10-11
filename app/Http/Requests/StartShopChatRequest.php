<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartShopChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $shop = $this->route('shop');

        return $user !== null
            && in_array($user->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER], true)
            && (bool) $user->is_active
            && $shop instanceof User
            && $shop->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shop->is_active
            && $user->getKey() !== $shop->getKey();
    }

    public function rules(): array
    {
        $shop = $this->route('shop');

        return [
            'product' => [
                'nullable',
                'required_with:variant',
                'uuid',
                Rule::exists(ShopProduct::class, 'id')->where(
                    fn ($query) => $query
                        ->where('shop_owner_id', $shop->getKey())
                        ->where('is_active', true),
                ),
            ],
            'variant' => [
                'nullable',
                'uuid',
                Rule::exists('shop_product_variants', 'id')->where(
                    fn ($query) => $query
                        ->where('shop_product_id', $this->input('product'))
                        ->where('is_active', true),
                ),
            ],
            'client_message_id' => ['nullable', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'product.exists' => 'Sản phẩm không còn khả dụng tại cửa hàng này.',
        ];
    }
}
