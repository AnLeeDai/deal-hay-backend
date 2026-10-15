<?php

namespace App\Actions;

use App\Enums\TicketStatusEnum;
use App\Enums\TicketTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Mail\AccountStatusChangedMail;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReviewedNotification;
use App\Services\AddressService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReviewTicket
{
    public function __construct(private readonly NotificationService $notifications) {}

    /** @param array{status: string, admin_note?: string|null} $data */
    public function handle(Ticket $ticket, User $admin, array $data): Ticket
    {
        $reviewedTicket = DB::transaction(function () use ($admin, $data, $ticket): Ticket {
            $lockedTicket = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->getKey());

            if ($lockedTicket->status !== TicketStatusEnum::Pending) {
                throw ValidationException::withMessages([
                    'general' => 'Ticket này đã được xử lý bởi quản trị viên khác.',
                ]);
            }

            $lockedTicket->update([
                'status' => $data['status'],
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
            ]);

            if (
                $data['status'] === TicketStatusEnum::Approved
                && $lockedTicket->type === TicketTypeEnum::LockAppeal
            ) {
                User::query()
                    ->whereKey($lockedTicket->user_id)
                    ->update([
                        'is_active' => UserStatusEnum::ACTIVE,
                        'lock_reason' => null,
                    ]);
            }

            if (
                $data['status'] === TicketStatusEnum::Approved
                && $lockedTicket->type === TicketTypeEnum::ShopOwnerUpgrade
            ) {
                $this->upgradeUserToShopOwner($lockedTicket);
            }

            return $lockedTicket->refresh()->load(['user', 'reviewer']);
        });

        $this->notifyUser($reviewedTicket, $data['status']);

        return $reviewedTicket;
    }

    private function upgradeUserToShopOwner(Ticket $ticket): void
    {
        $details = $ticket->metadata['shop_owner_upgrade'] ?? null;

        if (! is_array($details)) {
            throw ValidationException::withMessages([
                'general' => 'Ticket nâng cấp Chủ Shop không có thông tin hợp lệ.',
            ]);
        }

        if (! app(AddressService::class)->wardBelongsToProvince($details['ward'] ?? null, $details['province'] ?? null)) {
            throw ValidationException::withMessages([
                'general' => 'Địa chỉ trong yêu cầu không hợp lệ. Vui lòng yêu cầu người dùng gửi lại tỉnh/thành và phường/xã từ danh sách.',
            ]);
        }

        $user = User::query()->lockForUpdate()->findOrFail($ticket->user_id);

        if ($user->role !== UserRoleEnum::CUSTOMER) {
            throw ValidationException::withMessages([
                'general' => 'Tài khoản này không còn đủ điều kiện để nâng cấp Chủ Shop.',
            ]);
        }

        $user->update([
            'role' => UserRoleEnum::SHOP_OWNER,
            'user_code' => $user->generateUserCode(UserRoleEnum::SHOP_OWNER),
            'full_name' => $details['full_name'],
            'shop_name' => $details['shop_name'],
            'email' => $details['email'],
            'shop_email' => $details['shop_email'],
            'phone_number' => $details['phone_number'],
            'shop_phone_number' => $details['shop_phone_number'],
            'province' => $details['province'],
            'ward' => $details['ward'],
            'address_detail' => $details['address_detail'],
        ]);
    }

    private function notifyUser(Ticket $ticket, string $status): void
    {
        if ($ticket->user === null) {
            return;
        }

        $notification = new TicketReviewedNotification($ticket);
        $ticket->user->notify($notification);

        if ($status === TicketStatusEnum::Approved && $ticket->type === TicketTypeEnum::LockAppeal) {
            try {
                $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
                Mail::to($ticket->user->email)->send(new AccountStatusChangedMail(
                    fullName: $ticket->user->full_name,
                    email: $ticket->user->email,
                    isActive: true,
                    lockReason: null,
                    signInUrl: $frontendUrl.'/sign-in',
                ));
            } catch (Throwable $throwable) {
                report($throwable);
            }
        }

        $storedNotification = $ticket->user->notifications()->find($notification->id);

        if ($storedNotification !== null) {
            $this->notifications->broadcast($storedNotification);
        }
    }
}
