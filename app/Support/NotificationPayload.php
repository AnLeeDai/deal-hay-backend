<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;

class NotificationPayload
{
    /** @return array<string, mixed> */
    public static function from(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => (string) $notification->id,
            'title' => $data['title'] ?? 'Thông báo',
            'body' => $data['body'] ?? '',
            'category' => $data['category'] ?? 'general',
            'user_id' => $data['user_id'] ?? null,
            'ticket_id' => $data['ticket_id'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'data' => $data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
