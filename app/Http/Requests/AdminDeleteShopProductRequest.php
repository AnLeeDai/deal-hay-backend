<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminDeleteShopProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::ADMINISTRATOR;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Vui lòng nhập lý do xoá sản phẩm.',
            'reason.string' => 'Lý do xoá sản phẩm phải là chuỗi ký tự.',
            'reason.min' => 'Lý do xoá sản phẩm phải có ít nhất 5 ký tự.',
            'reason.max' => 'Lý do xoá sản phẩm không được vượt quá 1000 ký tự.',
        ];
    }
}
