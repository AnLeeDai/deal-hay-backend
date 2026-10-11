<?php

namespace App\Http\Requests;

use App\Enums\TicketSourceEnum;
use App\Enums\TicketTypeEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $userId = $this->string('user_id')->toString();

        if ($userId === '') {
            return false;
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            return false;
        }

        $type = $this->string('type')->toString();

        if ($type === TicketTypeEnum::LockAppeal) {
            return (int) $user->is_active === UserStatusEnum::INACTIVE;
        }

        if ($type === TicketTypeEnum::NotificationAppeal) {
            $notificationId = $this->string('notification_id')->toString();

            return (string) $this->user()?->getKey() === $userId
                && $notificationId !== ''
                && $user->notifications()->whereKey($notificationId)->exists();
        }

        return false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->string('type')->toString();
        $expectedSource = $type === TicketTypeEnum::NotificationAppeal
            ? TicketSourceEnum::NotificationCenter
            : TicketSourceEnum::LockedAccountDialog;

        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'type' => ['required', 'string', 'in:'.implode(',', [
                TicketTypeEnum::LockAppeal,
                TicketTypeEnum::NotificationAppeal,
            ])],
            'source' => ['nullable', 'string', Rule::in([$expectedSource])],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'notification_id' => [
                Rule::requiredIf($type === TicketTypeEnum::NotificationAppeal),
                Rule::prohibitedIf($type !== TicketTypeEnum::NotificationAppeal),
                'nullable',
                'uuid',
                'exists:notifications,id',
            ],
            'notification_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Không tìm thấy tài khoản cần hỗ trợ.',
            'user_id.uuid' => 'Tài khoản cần hỗ trợ không hợp lệ.',
            'user_id.exists' => 'Tài khoản cần hỗ trợ không tồn tại.',
            'type.required' => 'Vui lòng chọn loại yêu cầu.',
            'type.string' => 'Loại yêu cầu phải là chuỗi ký tự.',
            'type.in' => 'Loại yêu cầu không hợp lệ.',
            'source.string' => 'Nguồn gửi yêu cầu phải là chuỗi ký tự.',
            'source.in' => 'Nguồn gửi yêu cầu không hợp lệ.',
            'message.required' => 'Vui lòng nhập nội dung yêu cầu.',
            'message.string' => 'Nội dung yêu cầu phải là chuỗi ký tự.',
            'message.min' => 'Nội dung yêu cầu phải có ít nhất 10 ký tự.',
            'message.max' => 'Nội dung yêu cầu không được vượt quá 1000 ký tự.',
            'notification_id.required' => 'Không tìm thấy thông báo cần khiếu nại.',
            'notification_id.prohibited' => 'Yêu cầu mở khóa không được đính kèm thông báo.',
            'notification_id.uuid' => 'Thông báo cần khiếu nại không hợp lệ.',
            'notification_id.exists' => 'Thông báo cần khiếu nại không tồn tại.',
            'notification_title.string' => 'Tiêu đề thông báo phải là chuỗi ký tự.',
            'notification_title.max' => 'Tiêu đề thông báo không được vượt quá 255 ký tự.',
        ];
    }
}
