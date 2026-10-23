<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;

class ShopStocktakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::SHOP_OWNER && (bool) $this->user()?->is_active;
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'store' => ['name' => ['required', 'string', 'max:120']],
            'scan' => [
                'code' => ['required', 'string', 'max:255'],
                'quantity' => ['sometimes', 'integer', 'min:1', 'max:999999'],
                'scan_id' => ['required', 'uuid'],
            ],
            'updateItem' => ['counted_quantity' => ['required', 'integer', 'min:0', 'max:999999']],
            'complete' => ['adjust_stock' => ['required', 'boolean']],
            default => [],
        };
    }
}
