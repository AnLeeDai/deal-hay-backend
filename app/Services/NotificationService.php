<?php

namespace App\Services;

use App\Events\UserNotificationCreatedEvent;
use App\Events\UserNotificationsReadEvent;
use App\Models\User;
use App\Support\NotificationPayload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
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

        $this->markVisibleAsRead($user, [(string) $notification->getKey()]);
    }

    public function markAllAsRead(User $user): void
    {
        $this->markVisibleAsRead($user);
    }

    /** @param list<string>|null $notificationIds */
    public function markVisibleAsRead(User $user, ?array $notificationIds = null): void
    {
        DB::transaction(function () use ($user, $notificationIds): void {
            $notifications = $user->unreadNotifications()
                ->when($notificationIds !== null, fn ($query) => $query->whereKey($notificationIds))
                ->select('id')->lockForUpdate()->get();
            if ($notifications->isEmpty()) {
                return;
            }

            $readAt = now();
            foreach (array_chunk($notifications->modelKeys(), 100) as $ids) {
                $user->unreadNotifications()->whereKey($ids)->update(['read_at' => $readAt]);
                UserNotificationsReadEvent::dispatch(
                    (string) $user->getKey(),
                    array_map('strval', $ids),
                    $readAt->toIso8601String(),
                );
            }
        }, attempts: 5);
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
