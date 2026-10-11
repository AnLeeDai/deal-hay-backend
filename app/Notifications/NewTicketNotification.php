<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewTicketNotification extends Notification
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
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return [
            'title' => 'Ticket mới cần xử lý',
            'body' => sprintf(
                '%s vừa gửi yêu cầu: %s',
                $this->ticket->user?->full_name ?? 'Người dùng',
                Str::limit($this->ticket->message, 90),
            ),
            'category' => 'ticket.created',
            'ticket_id' => $this->ticket->id,
            'action_url' => $frontendUrl.'/admin/tickets/'.$this->ticket->getKey(),
        ];
    }
}
