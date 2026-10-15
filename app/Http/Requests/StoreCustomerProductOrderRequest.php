<?php

namespace App\Http\Requests;

use App\Models\ShopProduct;
use App\Rules\ValidProvince;
use App\Rules\ValidWardInProvince;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerProductOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function authorize(): bool
    {
        $product = $this->route('shopProduct');

        return $this->user()?->is_active && $product instanceof ShopProduct
            && $product->shop_owner_id !== $this->user()?->getKey();
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'variant_id' => ['nullable', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'regex:/^(\+84|0)[3-9]\d{8}$/'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_province_code' => ['required', 'integer', new ValidProvince(true)],
            'shipping_ward_code' => ['required', 'integer', new ValidWardInProvince($this->input('shipping_province_code'), true)],
            'note' => ['nullable', 'string', 'max:2000'],
            'advertisement_view_id' => ['nullable', 'uuid'],
        ];
    }
}
