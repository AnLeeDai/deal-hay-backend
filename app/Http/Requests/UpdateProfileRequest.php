<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower($this->string('email')->squish()->toString()),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(fn ($query) => $query->where('role', $user?->role))
                    ->ignore($user?->getKey()),
            ],
            'phone_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^(\+84|0)[3-9]\d{8}$/',
                Rule::unique('users', 'phone_number')
                    ->where(fn ($query) => $query->where('role', $user?->role))
                    ->ignore($user?->getKey()),
            ],
            'province' => ['sometimes', 'string', 'max:255'],
            'ward' => ['sometimes', 'string', 'max:255'],
            'address_detail' => ['sometimes', 'string', 'max:255'],
            'email_change_code' => ['sometimes', 'nullable', 'digits:6'],
            'avatar' => ['sometimes', 'file'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.string' => 'Họ và tên phải là chuỗi ký tự.',
            'full_name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.string' => 'Email phải là chuỗi ký tự.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email này đã được sử dụng cho loại tài khoản này.',
            'phone_number.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone_number.regex' => 'Số điện thoại không hợp lệ.',
            'phone_number.unique' => 'Số điện thoại này đã được sử dụng.',
            'province.string' => 'Tỉnh hoặc thành phố phải là chuỗi ký tự.',
            'province.max' => 'Tỉnh hoặc thành phố không được vượt quá 255 ký tự.',
            'ward.string' => 'Phường hoặc xã phải là chuỗi ký tự.',
            'ward.max' => 'Phường hoặc xã không được vượt quá 255 ký tự.',
            'address_detail.string' => 'Địa chỉ chi tiết phải là chuỗi ký tự.',
            'address_detail.max' => 'Địa chỉ chi tiết không được vượt quá 255 ký tự.',
            'email_change_code.digits' => 'Mã xác thực phải gồm đúng 6 chữ số.',
            'avatar.file' => 'Ảnh đại diện phải là một tệp hợp lệ.',
        ];
    }
}
