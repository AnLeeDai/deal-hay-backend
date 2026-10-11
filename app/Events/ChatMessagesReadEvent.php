<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ChatMessagesReadEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tries = 5;

    public string $connection = 'database_outbox';

    /** @var list<int> */
    public array $backoff = [1, 5, 15, 30];

    public int $timeout = 15;

    public function __construct(
        public readonly string $conversationId,
        public readonly string $readerId,
        /** @var list<string> */
        public readonly array $messageIds,
        public readonly string $readAt,
        public readonly string $readThroughAt,
    ) {}

    public function broadcastQueue(): string
    {
        return (string) config('queue.connections.database_outbox.queue', 'realtime');
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('chat.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_id' => $this->readerId,
            'message_ids' => $this->messageIds,
            'read_at' => $this->readAt,
            'read_through_at' => $this->readThroughAt,
        ];
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }
    }
}
