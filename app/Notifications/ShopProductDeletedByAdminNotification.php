<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ShopProductDeletedByAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly array $productSnapshot,
        public readonly string $reason,
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
            'title' => 'Sản phẩm đã bị admin xoá',
            'body' => sprintf(
                'Sản phẩm "%s" đã bị xoá khỏi shop. Lý do: %s',
                $this->productSnapshot['name'] ?? 'Không rõ tên',
                $this->reason,
            ),
            'category' => 'shop.product.deleted_by_admin',
            'product' => $this->productSnapshot,
            'reason' => $this->reason,
            'action_url' => $frontendUrl.'/shop/manage',
        ];
    }
}
