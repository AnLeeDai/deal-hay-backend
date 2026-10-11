<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::SHOP_OWNER;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'shop_name' => ['sometimes', 'string', 'max:255'],
            'shop_email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'shop_email')
                    ->where(fn ($query) => $query->where('role', UserRoleEnum::SHOP_OWNER))
                    ->ignore($user?->getKey()),
            ],
            'shop_phone_number' => [
                'sometimes',
                'string',
                'regex:/^(\+84|0)[3-9]\d{8}$/',
                Rule::unique('users', 'shop_phone_number')
                    ->where(fn ($query) => $query->where('role', UserRoleEnum::SHOP_OWNER))
                    ->ignore($user?->getKey()),
            ],
            'shop_province' => ['sometimes', 'string', 'max:255'],
            'shop_ward' => ['sometimes', 'string', 'max:255'],
            'shop_address_detail' => ['sometimes', 'string', 'max:255'],
            'email_change_code' => ['sometimes', 'nullable', 'digits:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shop_name.required' => 'Vui lòng nhập tên shop.',
            'shop_name.string' => 'Tên shop phải là chuỗi ký tự.',
            'shop_name.max' => 'Tên shop không được vượt quá 255 ký tự.',
            'shop_email.required' => 'Vui lòng nhập email shop.',
            'shop_email.string' => 'Email shop phải là chuỗi ký tự.',
            'shop_email.email' => 'Email shop không đúng định dạng.',
            'shop_email.max' => 'Email shop không được vượt quá 255 ký tự.',
            'shop_email.unique' => 'Email shop này đã được sử dụng.',
            'shop_phone_number.required' => 'Vui lòng nhập số điện thoại shop.',
            'shop_phone_number.string' => 'Số điện thoại shop phải là chuỗi ký tự.',
            'shop_phone_number.regex' => 'Số điện thoại shop không hợp lệ.',
            'shop_phone_number.unique' => 'Số điện thoại shop này đã được sử dụng.',
            'shop_province.string' => 'Tỉnh hoặc thành phố của shop phải là chuỗi ký tự.',
            'shop_province.max' => 'Tỉnh hoặc thành phố của shop không được vượt quá 255 ký tự.',
            'shop_ward.string' => 'Phường hoặc xã của shop phải là chuỗi ký tự.',
            'shop_ward.max' => 'Phường hoặc xã của shop không được vượt quá 255 ký tự.',
            'shop_address_detail.string' => 'Địa chỉ chi tiết của shop phải là chuỗi ký tự.',
            'shop_address_detail.max' => 'Địa chỉ chi tiết của shop không được vượt quá 255 ký tự.',
            'email_change_code.digits' => 'Mã xác thực phải gồm đúng 6 chữ số.',
        ];
    }
}
