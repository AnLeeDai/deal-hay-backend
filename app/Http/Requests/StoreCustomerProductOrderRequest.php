<?php

namespace App\Http\Requests;

use App\Models\ShopProduct;
use App\Rules\ValidProvince;
use App\Rules\ValidWardInProvince;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerProductOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        if (is_string($this->input('discount_code'))) {
            $this->merge(['discount_code' => strtoupper(trim($this->input('discount_code')))]);
        }
        if (is_string($this->input('shipping_discount_code'))) {
            $this->merge(['shipping_discount_code' => strtoupper(trim($this->input('shipping_discount_code')))]);
        }
        if (is_array($this->input('discount_codes'))) {
            $this->merge(['discount_codes' => array_map(fn ($code) => is_string($code) ? strtoupper(trim($code)) : $code, $this->input('discount_codes'))]);
        }
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
            'quoted_shipping_fee' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'shipping_discount_code' => ['nullable', 'string', 'size:32'],
            'note' => ['nullable', 'string', 'max:2000'],
            'advertisement_view_id' => ['nullable', 'uuid'],
            'discount_code' => ['nullable', 'string', 'size:32', Rule::prohibitedIf($this->filled('advertisement_view_id') || $this->filled('discount_codes'))],
            'discount_codes' => ['nullable', 'array', 'list', 'max:100', Rule::prohibitedIf($this->filled('advertisement_view_id') || $this->filled('discount_code'))],
            'discount_codes.*' => ['required', 'string', 'size:32', 'distinct'],
        ];
    }
}
