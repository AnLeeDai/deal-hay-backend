<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class ShopInventoryUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::SHOP_OWNER && (bool) $this->user()?->is_active;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'product_id' => ['sometimes', 'nullable', 'uuid'],
        ];
    }
}
