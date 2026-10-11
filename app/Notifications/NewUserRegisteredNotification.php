<?php

namespace App\Notifications;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly User $user) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return [
            'title' => 'Người dùng mới đăng ký',
            'body' => sprintf(
                '%s vừa tạo tài khoản %s với email %s.',
                $this->user->full_name,
                $this->roleLabel(),
                $this->user->email,
            ),
            'category' => 'user.created',
            'user_id' => $this->user->id,
            'action_url' => $frontendUrl.'/admin/users',
        ];
    }

    private function roleLabel(): string
    {
        return match ($this->user->role) {
            UserRoleEnum::SHOP_OWNER => 'chủ cửa hàng',
            UserRoleEnum::CUSTOMER => 'người dùng',
            default => 'người dùng',
        };
    }
}
