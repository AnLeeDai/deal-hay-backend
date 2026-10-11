<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminToggleLockUserRequest extends FormRequest
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
            'action' => ['required', 'in:lock,unlock'],
            'lock_reason' => ['required_if:action,lock', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Vui lòng chọn thao tác.',
            'action.in' => 'Thao tác không hợp lệ.',
            'lock_reason.required_if' => 'Vui lòng nhập lý do khóa tài khoản.',
            'lock_reason.string' => 'Lý do khóa tài khoản phải là chuỗi ký tự.',
            'lock_reason.max' => 'Lý do khóa tài khoản không được vượt quá 500 ký tự.',
        ];
    }
}
