<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof ChatConversation
            && $this->user() !== null
            && (bool) $this->user()->is_active
            && $conversation->includes($this->user());
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'client_message_id' => ['required', 'uuid', 'same:idempotency_key'],
            'body' => ['nullable', 'required_without:attachment', 'string', 'max:2000'],
            'attachment' => [
                'nullable',
                'file',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'Thiếu header Idempotency-Key cho tin nhắn.',
            'idempotency_key.uuid' => 'Header Idempotency-Key phải là UUID hợp lệ.',
            'client_message_id.required' => 'Thiếu client_message_id cho tin nhắn.',
            'client_message_id.uuid' => 'client_message_id phải là UUID hợp lệ.',
            'client_message_id.same' => 'client_message_id phải trùng với header Idempotency-Key.',
            'body.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'body.required_without' => 'Vui lòng nhập nội dung hoặc chọn tệp đính kèm.',
            'body.max' => 'Tin nhắn không được vượt quá 2.000 ký tự.',
        ];
    }
}
