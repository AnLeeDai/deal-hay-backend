<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreShopOwnerUpgradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::CUSTOMER;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => $this->string('full_name')->squish()->toString(),
            'shop_name' => $this->string('shop_name')->squish()->toString(),
            'email' => Str::lower($this->string('email')->squish()->toString()),
            'shop_email' => Str::lower($this->string('shop_email')->squish()->toString()),
            'phone_number' => $this->string('phone_number')->squish()->toString(),
            'shop_phone_number' => $this->string('shop_phone_number')->squish()->toString(),
            'province' => $this->string('province')->squish()->toString(),
            'ward' => $this->string('ward')->squish()->toString(),
            'address_detail' => $this->string('address_detail')->squish()->toString(),
            'message' => $this->string('message')->squish()->toString(),
        ]);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'shop_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', Rule::unique('users', 'email')->ignore($user)],
            'shop_email' => ['required', 'string', 'email', Rule::unique('users', 'shop_email')->where('role', UserRoleEnum::SHOP_OWNER)->ignore($user)],
            'phone_number' => ['required', 'string', 'regex:/^(\+84|0)[3-9]\d{8}$/', Rule::unique('users', 'phone_number')->where('role', UserRoleEnum::SHOP_OWNER)->ignore($user)],
            'shop_phone_number' => ['required', 'string', 'regex:/^(\+84|0)[3-9]\d{8}$/', Rule::unique('users', 'shop_phone_number')->where('role', UserRoleEnum::SHOP_OWNER)->ignore($user)],
            'province' => ['required', 'string', 'max:255'],
            'ward' => ['required', 'string', 'max:255'],
            'address_detail' => ['required', 'string', 'max:255'],
            'message' => ['string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'shop_name.required' => 'Vui lòng nhập tên shop.',
            'email.required' => 'Vui lòng nhập email cá nhân.',
            'email.email' => 'Email cá nhân không đúng định dạng.',
            'email.unique' => 'Email cá nhân này đã được sử dụng.',
            'shop_email.required' => 'Vui lòng nhập email shop.',
            'shop_email.email' => 'Email shop không đúng định dạng.',
            'shop_email.unique' => 'Email shop này đã được sử dụng.',
            'phone_number.required' => 'Vui lòng nhập số điện thoại cá nhân.',
            'phone_number.regex' => 'Số điện thoại cá nhân không đúng định dạng.',
            'phone_number.unique' => 'Số điện thoại cá nhân này đã được sử dụng.',
            'shop_phone_number.required' => 'Vui lòng nhập số điện thoại shop.',
            'shop_phone_number.regex' => 'Số điện thoại shop không đúng định dạng.',
            'shop_phone_number.unique' => 'Số điện thoại shop này đã được sử dụng.',
            'province.required' => 'Vui lòng nhập tỉnh/thành phố.',
            'ward.required' => 'Vui lòng nhập phường/xã/đặc khu.',
            'address_detail.required' => 'Vui lòng nhập số nhà, tên đường.',
        ];
    }
}
