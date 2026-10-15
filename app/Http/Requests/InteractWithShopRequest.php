<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InteractWithShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $shop = $this->route('shop');

        return $user !== null
            && $shop instanceof User
            && in_array($user->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER, UserRoleEnum::ADMINISTRATOR], true)
            && (bool) $user->is_active
            && $shop->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shop->is_active
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
