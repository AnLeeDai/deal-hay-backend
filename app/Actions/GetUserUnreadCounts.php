<?php

namespace App\Actions;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GetUserUnreadCounts
{
    /**
     * @return array{unread_messages_count: int, unread_notifications_count: int}
     */
    public function handle(User $user): array
    {
        $userId = $user->getKey();
        $unreadMessagesCount = ChatMessage::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation', function (Builder $query) use ($userId): void {
                $query->where(function (Builder $participants) use ($userId): void {
                    $participants
                        ->where('buyer_id', $userId)
                        ->orWhere('shop_owner_id', $userId);
                });
            })
            ->count();

        return [
            'unread_messages_count' => $unreadMessagesCount,
            'unread_notifications_count' => $user->unreadNotifications()->count(),
        ];
    }
}
