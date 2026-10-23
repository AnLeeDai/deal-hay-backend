<?php

namespace App\Events;

use App\Support\NotificationPayload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\SerializesModels;

class UserNotificationCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $connection = 'database_outbox';

    public int $tries = 5;

    public int $timeout = 15;

    /** @var list<int> */
    public array $backoff = [1, 5, 15, 30];

    public function __construct(public readonly DatabaseNotification $notification) {}

    public function broadcastQueue(): string
    {
        return (string) config('queue.connections.database_outbox.queue', 'realtime');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user-notifications.'.$this->notification->notifiable_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return NotificationPayload::from($this->notification);
    }
}
