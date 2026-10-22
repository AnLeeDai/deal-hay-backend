<?php

namespace App\Actions;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GetUserUnreadCounts
{
    /**
     * @return array{unread_messages_count: int, unread_notifications_count: int}
     */
    public function handle(User $user): array
    {
        $userId = $user->getKey();
        $unreadMessages = ChatMessage::query()
            ->selectRaw('COUNT(*)')
            ->whereNull('read_at')
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation', function (Builder $query) use ($userId): void {
                $query->where(function (Builder $participants) use ($userId): void {
                    $participants
                        ->where('buyer_id', $userId)
                        ->orWhere('shop_owner_id', $userId);
                });
            });
        $counts = DB::query()
            ->selectSub($unreadMessages, 'unread_messages_count')
            ->selectSub($user->unreadNotifications()->reorder()->selectRaw('COUNT(*)'), 'unread_notifications_count')
            ->first();

        return [
            'unread_messages_count' => (int) $counts->unread_messages_count,
            'unread_notifications_count' => (int) $counts->unread_notifications_count,
        ];
    }
}
