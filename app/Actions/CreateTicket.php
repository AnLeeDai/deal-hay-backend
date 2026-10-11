<?php

namespace App\Actions;

use App\Enums\TicketSourceEnum;
use App\Enums\TicketStatusEnum;
use App\Enums\TicketTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTicket
{
    public function __construct(private readonly NotificationService $notifications) {}

    /** @param array<string, mixed> $data */
    public function handle(string $userId, array $data, ?string $sourceUrl): Ticket
    {
        $ticket = DB::transaction(function () use ($data, $sourceUrl, $userId): Ticket {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($userId);
            $type = (string) $data['type'];
            $source = $type === TicketTypeEnum::NotificationAppeal
                ? TicketSourceEnum::NotificationCenter
                : TicketSourceEnum::LockedAccountDialog;
            $notification = $this->notification($lockedUser, $type, $data);

            $this->ensureNoPendingDuplicate($lockedUser, $type, $notification);

            return Ticket::query()->create([
                'user_id' => $lockedUser->getKey(),
                'type' => $type,
                'source' => $source,
                'message' => $data['message'],
                'metadata' => [
                    'source_url' => $sourceUrl,
                    'submitted_from' => TicketSourceEnum::label($source),
                    'lock_reason' => $type === TicketTypeEnum::LockAppeal
                        ? $lockedUser->lock_reason
                        : null,
                    'notification_id' => $notification?->getKey(),
                    'notification_title' => $notification?->data['title'] ?? null,
                ],
                'status' => TicketStatusEnum::Pending,
            ]);
        });

        $ticket->loadMissing('user');
        $this->notifyAdministrators($ticket);

        return $ticket;
    }

    private function notifyAdministrators(Ticket $ticket): void
    {
        User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->each(function (User $admin) use ($ticket): void {
            $notification = new NewTicketNotification($ticket);
            $admin->notify($notification);
            $storedNotification = $admin->notifications()->find($notification->id);

            if ($storedNotification !== null) {
                $this->notifications->broadcast($storedNotification);
            }
        });
    }

    /** @param array<string, mixed> $data */
    private function notification(User $user, string $type, array $data): ?DatabaseNotification
    {
        if ($type !== TicketTypeEnum::NotificationAppeal) {
            return null;
        }

        return $user->notifications()
            ->whereKey((string) $data['notification_id'])
            ->firstOrFail();
    }

    private function ensureNoPendingDuplicate(
        User $user,
        string $type,
        ?DatabaseNotification $notification,
    ): void {
        $pendingTicket = Ticket::query()
            ->whereBelongsTo($user)
            ->where('type', $type)
            ->where('status', TicketStatusEnum::Pending)
            ->when(
                $notification !== null,
                fn ($query) => $query->where('metadata->notification_id', $notification->getKey()),
            )
            ->exists();

        if ($pendingTicket) {
            throw ValidationException::withMessages([
                'message' => $type === TicketTypeEnum::NotificationAppeal
                    ? 'Thông báo này đã có khiếu nại đang chờ xử lý.'
                    : 'Bạn đã có yêu cầu mở khóa đang chờ xử lý.',
            ]);
        }
    }
}
