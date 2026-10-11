<?php

namespace App\Http\Requests;

use App\Models\ChatMessage;
use Illuminate\Foundation\Http\FormRequest;

class ShowChatAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $message = $this->route('message');

        return $message instanceof ChatMessage
            && $this->user() !== null
            && (bool) $this->user()->is_active
            && $message->conversation->includes($this->user());
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
