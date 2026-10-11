<?php

namespace App\Http\Requests;

use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminShopsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $search = $this->query('search', '');
        $status = $this->query('status', 'all');

        $this->merge([
            'search' => is_string($search) ? trim($search) : '',
            'status' => is_string($status) && in_array($status, ['active', 'locked'], true)
                ? $status
                : 'all',
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->role === UserRoleEnum::ADMINISTRATOR;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['all', 'active', 'locked'])],
        ];
    }
}
