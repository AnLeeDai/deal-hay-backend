<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StartAdminChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $administrator = $this->user();
        $recipient = $this->route('user');

        return $administrator !== null
            && $administrator->role === UserRoleEnum::ADMINISTRATOR
            && (bool) $administrator->is_active
            && $recipient instanceof User
            && $administrator->getKey() !== $recipient->getKey();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
