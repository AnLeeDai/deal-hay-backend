<?php

namespace App\Notifications;

use App\Models\ShopOrder;
use Illuminate\Notifications\Notification;

class ShopOrderNotification extends Notification
{
    public function __construct(
        public readonly ShopOrder $order,
        public readonly string $event,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $isShopOwner = (string) $notifiable->getKey() === (string) $this->order->shop_owner_id;
        $status = match ($this->order->status) {
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'processing' => 'Đang chuẩn bị',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Đã hoàn thành',
            'cancelled' => 'Đã hủy',
            default => $this->order->status,
        };

        return [
            'title' => $this->event === 'created'
                ? ($isShopOwner ? 'Có đơn hàng mới' : 'Đặt hàng thành công')
                : 'Cập nhật đơn hàng '.$this->order->order_number,
            'body' => $this->event === 'created'
                ? ($isShopOwner
                    ? "Khách hàng {$this->order->customer_name} vừa đặt đơn {$this->order->order_number}."
                    : "Đơn {$this->order->order_number} đã được gửi đến cửa hàng và đang chờ xác nhận.")
                : "Đơn {$this->order->order_number}: {$status}.",
            'category' => 'order.'.$this->event,
            'order_id' => (string) $this->order->getKey(),
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'action_url' => $isShopOwner ? '/shop-manage?tab=orders' : '/notifications',
        ];
    }
}
