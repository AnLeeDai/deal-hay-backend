<?php

namespace App\Http\Requests;

use App\Enums\TicketStatusEnum;
use App\Enums\UserRoleEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::ADMINISTRATOR;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.TicketStatusEnum::Approved.','.TicketStatusEnum::Rejected],
            'admin_note' => [
                Rule::requiredIf($this->string('status')->toString() === TicketStatusEnum::Rejected),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái ticket.',
            'status.string' => 'Trạng thái ticket phải là chuỗi ký tự.',
            'status.in' => 'Trạng thái ticket không hợp lệ.',
            'admin_note.required' => 'Vui lòng nhập lý do từ chối ticket.',
            'admin_note.string' => 'Ghi chú của quản trị viên phải là chuỗi ký tự.',
            'admin_note.max' => 'Ghi chú của quản trị viên không được vượt quá 1000 ký tự.',
        ];
    }
}
