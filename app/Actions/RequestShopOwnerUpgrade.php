<?php

namespace App\Actions;

use App\Enums\TicketSourceEnum;
use App\Enums\TicketTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestShopOwnerUpgrade
{
    public function __construct(private readonly NotificationService $notifications) {}

    /** @param array{full_name: string, shop_name: string, email: string, shop_email: string, phone_number: string, shop_phone_number: string, province: string, ward: string, address_detail: string, message: string} $data */
    public function handle(User $user, array $data, ?string $sourceUrl): Ticket
    {
        $ticket = DB::transaction(function () use ($data, $sourceUrl, $user): Ticket {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($lockedUser->role !== UserRoleEnum::CUSTOMER) {
                throw ValidationException::withMessages([
                    'message' => 'Chỉ tài khoản người dùng mới có thể đăng ký nâng cấp Chủ Shop.',
                ]);
            }

            $hasPendingRequest = Ticket::query()
                ->whereBelongsTo($lockedUser)
                ->where('type', TicketTypeEnum::ShopOwnerUpgrade)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingRequest) {
                throw ValidationException::withMessages([
                    'message' => 'Bạn đã có yêu cầu nâng cấp Chủ Shop đang chờ xử lý.',
                ]);
            }

            return Ticket::query()->create([
                'user_id' => $lockedUser->getKey(),
                'type' => TicketTypeEnum::ShopOwnerUpgrade,
                'source' => TicketSourceEnum::UserMenu,
                'message' => $data['message'],
                'metadata' => [
                    'source_url' => $sourceUrl,
                    'submitted_from' => TicketSourceEnum::label(TicketSourceEnum::UserMenu),
                    'shop_owner_upgrade' => collect($data)->except('message')->all(),
                ],
                'status' => 'pending',
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
}
