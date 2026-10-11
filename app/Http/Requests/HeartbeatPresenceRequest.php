<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HeartbeatPresenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) $this->user()->is_active;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => [
                'nullable',
                'uuid',
                Rule::exists(ChatConversation::class, 'id'),
            ],
        ];
    }
}
