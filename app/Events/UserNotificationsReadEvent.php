<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UserNotificationsReadEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public string $connection = 'database_outbox';

    public int $tries = 5;

    public int $timeout = 15;

    /** @var list<int> */
    public array $backoff = [1, 5, 15, 30];

    /** @param list<string> $notificationIds */
    public function __construct(
        public readonly string $userId,
        public readonly array $notificationIds,
        public readonly string $readAt,
    ) {}

    public function broadcastQueue(): string
    {
        return (string) config('queue.connections.database_outbox.queue', 'realtime');
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user-notifications.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'notifications.read';
    }

    /** @return array{notification_ids: list<string>, read_at: string} */
    public function broadcastWith(): array
    {
        return ['notification_ids' => $this->notificationIds, 'read_at' => $this->readAt];
    }
}
