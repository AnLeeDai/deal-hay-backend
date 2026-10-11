<?php

namespace App\Notifications;

use App\Enums\TicketStatusEnum;
use App\Enums\TicketTypeEnum;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Ticket $ticket) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $approved = $this->ticket->status === TicketStatusEnum::Approved;
        $lockAppeal = $this->ticket->type === TicketTypeEnum::LockAppeal;
        $shopOwnerUpgrade = $this->ticket->type === TicketTypeEnum::ShopOwnerUpgrade;
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return [
            'title' => $approved ? 'Ticket đã được duyệt' : 'Ticket đã bị từ chối',
            'body' => match (true) {
                $approved && $lockAppeal => 'Tài khoản của bạn đã được mở khóa.',
                $approved && $shopOwnerUpgrade => 'Tài khoản của bạn đã được nâng cấp thành Chủ Shop.',
                $approved => $this->ticket->admin_note ?: 'Khiếu nại của bạn đã được chấp thuận.',
                default => $this->ticket->admin_note ?: 'Yêu cầu của bạn chưa được chấp thuận.',
            },
            'category' => $approved ? 'ticket.approved' : 'ticket.rejected',
            'ticket_id' => $this->ticket->id,
            'action_url' => $lockAppeal
                ? $frontendUrl.'/sign-in'
                : ($shopOwnerUpgrade && $approved ? $frontendUrl.'/shop/manage' : $frontendUrl.'/notifications'),
        ];
    }
}
