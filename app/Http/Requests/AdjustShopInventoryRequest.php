<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use Illuminate\Foundation\Http\FormRequest;

class AdjustShopInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('shopProduct');

        return $this->user()?->role === UserRoleEnum::SHOP_OWNER
            && (bool) $this->user()?->is_active
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
            'shop_product_variant_id' => ['nullable', 'uuid'],
            'quantity_delta' => ['required', 'integer', 'not_in:0', 'min:-999999', 'max:999999'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function variantId(): ?string
    {
        $variantId = $this->validated('shop_product_variant_id');

        return is_string($variantId) ? $variantId : null;
    }

    public function quantityDelta(): int
    {
        return (int) $this->validated('quantity_delta');
    }

    public function note(): string
    {
        return (string) $this->validated('note');
    }
}
