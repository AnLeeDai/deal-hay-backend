<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

class MarkChatConversationReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof ChatConversation
            && $this->user() !== null
            && (bool) $this->user()->is_active
            && $conversation->includes($this->user());
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
