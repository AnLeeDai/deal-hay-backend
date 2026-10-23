<?php

namespace App\Actions;

use App\Models\ShopOrder;
use App\Models\User;
use App\Notifications\ShopOrderNotification;

class NotifyShopOrder
{
    public function created(ShopOrder $order): void
    {
        $this->send($order, 'created');
    }

    public function statusUpdated(ShopOrder $order): void
    {
        $this->send($order, 'status_updated');
    }

    private function send(ShopOrder $order, string $event): void
    {
        $recipientIds = array_values(array_unique(array_filter([
            $order->shop_owner_id,
            $order->customer_id,
        ])));

        foreach (User::query()->whereKey($recipientIds)->get() as $recipient) {
            $notification = new ShopOrderNotification($order, $event);
            $recipient->notify($notification);
        }
    }
}
