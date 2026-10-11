<?php

namespace App\Http\Requests;

use App\Enums\TicketSourceEnum;
use App\Enums\TicketStatusEnum;
use App\Enums\TicketTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in([
                TicketTypeEnum::LockAppeal,
                TicketTypeEnum::NotificationAppeal,
            ])],
            'status' => ['nullable', 'string', Rule::in([
                TicketStatusEnum::Pending,
                TicketStatusEnum::Approved,
                TicketStatusEnum::Rejected,
            ])],
            'source' => ['nullable', 'string', Rule::in([
                TicketSourceEnum::LockedAccountDialog,
                TicketSourceEnum::NotificationCenter,
            ])],
        ];
    }
}
