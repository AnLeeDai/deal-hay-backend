<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
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
        ]);
        $senderPayload = $this->message->sender->toArray();
        $product = $this->message->shopProduct;
        $productPayload = $product?->toArray();
        $variant = $this->message->shopProductVariant;

        return [
            'id' => (string) $this->message->id,
            'client_message_id' => (string) $this->message->client_message_id,
            'conversation_id' => (string) $this->message->chat_conversation_id,
            'body' => (string) $this->message->body,
            'read_at' => $this->message->read_at?->toISOString(),
            'attachment' => $this->message->attachment_mime ? [
                'url' => route('chats.attachments.show', $this->message),
                'name' => $this->message->attachment_name,
                'mime' => $this->message->attachment_mime,
                'size' => $this->message->attachment_size,
                'type' => match (true) {
                    Str::startsWith((string) $this->message->attachment_mime, 'image/') => 'image',
                    Str::startsWith((string) $this->message->attachment_mime, 'video/') => 'video',
                    default => 'file',
                },
            ] : null,
            'product' => $product ? [
                'id' => (string) $product->id,
                'shop_code' => (string) $product->shopOwner->user_code,
                'slug' => (string) $product->slug,
                'name' => (string) $product->name,
                'sku' => $variant?->sku ?? $product->sku,
                'price' => (int) ($variant?->price ?? $product->price),
                'variant_id' => $variant?->getKey(),
                'variant_label' => $variant?->displayName(),
                'option_values' => $variant?->optionValueMap() ?? [],
                'image_url' => $productPayload['image_url'] ?? null,
                'is_available' => (bool) $product->is_active
                    && (bool) $product->shopOwner->is_active
                    && ($variant !== null
                        ? (bool) $variant->is_active && (int) $variant->stock > 0
                        : ! $product->has_variants && (int) $product->stock > 0),
            ] : null,
            'sender' => [
                'id' => (string) $this->message->sender->id,
                'name' => (string) $this->message->sender->full_name,
                'avatar_url' => $senderPayload['avatar_url'],
            ],
            'created_at' => $this->message->created_at?->toISOString(),
        ];
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
