<?php

namespace App\Http\Requests;

use App\Rules\ValidProvince;
use App\Rules\ValidWardInProvince;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShippingQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_province_code' => ['required', 'integer', new ValidProvince(true)],
            'shipping_ward_code' => ['required', 'integer', new ValidWardInProvince($this->input('shipping_province_code'), true)],
        ];
    }
}
