<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Rules\ValidWardInProvince;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShopShippingSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::SHOP_OWNER && (bool) $this->user()?->is_active;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'base_fee' => ['required', 'integer', 'min:0', 'max:999999999'],
            'ward_rates' => ['present', 'array', 'list', 'max:500'],
            'ward_rates.*' => ['required', 'array:ward_code,fee'],
            'ward_rates.*.ward_code' => ['required', 'integer', 'distinct', new ValidWardInProvince(1, true)],
            'ward_rates.*.fee' => ['required', 'integer', 'min:0', 'max:999999999'],
        ];
    }
}
