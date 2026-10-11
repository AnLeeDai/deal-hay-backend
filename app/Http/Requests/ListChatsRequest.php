<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListChatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (bool) $this->user()->is_active;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [];
    }
}
