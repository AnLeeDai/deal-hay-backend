<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Services\ChatPayloadService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ChatMessageSentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tries = 5;

    public string $connection = 'database_outbox';

    /** @var list<int> */
    public array $backoff = [1, 5, 15, 30];

    public int $timeout = 15;

    public function __construct(public readonly ChatMessage $message) {}

    public function broadcastQueue(): string
    {
        return (string) config('queue.connections.database_outbox.queue', 'realtime');
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->message->chat_conversation_id),
            new PrivateChannel('user-chat.'.$this->message->sender_id),
            new PrivateChannel('user-chat.'.$this->recipientId()),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $this->message->loadMissing([
            'sender',
            'shopProduct.shopOwner',
            'shopProductVariant.optionValues.option',
            'replyTo.sender',
            'replyTo.shopProduct',
        ]);

        return app(ChatPayloadService::class)->message($this->message);
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }
    }

    private function recipientId(): string
    {
        $conversation = $this->message->conversation;

        return (string) ($conversation->buyer_id === $this->message->sender_id
            ? $conversation->shop_owner_id
            : $conversation->buyer_id);
    }
}
