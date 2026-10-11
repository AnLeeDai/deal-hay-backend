<?php

namespace App\Notifications;

use App\Models\ShopProfileChangeLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShopProfileUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly User $shopOwner,
        public readonly ShopProfileChangeLog $changeLog,
    ) {}

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
            'title' => 'Shop vừa cập nhật thông tin',
            'body' => sprintf(
                '%s vừa thay đổi %d mục thông tin cửa hàng.',
                $this->shopOwner->shop_name ?: $this->shopOwner->full_name,
                count($this->changeLog->changes ?? []),
            ),
            'category' => 'shop.profile.updated',
            'shop_owner_id' => $this->shopOwner->id,
            'shop_name' => $this->shopOwner->shop_name,
            'change_log_id' => $this->changeLog->id,
            'changes' => $this->changeLog->changes,
            'action_url' => $frontendUrl.'/admin/users',
        ];
    }
}
