<?php

namespace App\Services;

use App\Events\UserNotificationCreatedEvent;
use App\Models\User;
use App\Support\NotificationPayload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Throwable;

class NotificationService
{
    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    public function forUser(User $user): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn (DatabaseNotification $notification): array => NotificationPayload::from($notification));
    }

    public function markAsRead(User $user, DatabaseNotification $notification): void
    {
        abort_unless(
            $notification->notifiable_type === $user->getMorphClass()
            && (string) $notification->notifiable_id === (string) $user->getKey(),
            403,
        );

        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function broadcast(DatabaseNotification $notification): void
    {
        try {
            UserNotificationCreatedEvent::dispatch($notification);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
