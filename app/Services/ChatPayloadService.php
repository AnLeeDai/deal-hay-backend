<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Str;

class ChatPayloadService
{
    /** @return array<string, mixed> */
    public function conversation(
        ChatConversation $conversation,
        User $viewer,
        bool $withMessages = false,
        bool $counterpartIsOnline = false,
    ): array {
        $counterpart = $conversation->buyer_id === $viewer->getKey()
            ? $conversation->shopOwner
            : $conversation->buyer;
        $isShop = $conversation->buyer_id === $viewer->getKey();
        $counterpartPayload = $counterpart->toArray();
        $payload = [
            'id' => (string) $conversation->getKey(),
            'slug' => (string) $conversation->slug,
            'counterpart' => [
                'id' => (string) $counterpart->getKey(),
                'name' => (string) ($isShop
                    ? ($counterpart->shop_name ?: 'Cửa hàng DealHay')
                    : $counterpart->full_name),
                'avatar_url' => $isShop
                    ? $counterpartPayload['shop_avatar_url']
                    : $counterpartPayload['avatar_url'],
                'is_online' => $counterpartIsOnline,
                'is_shop' => $isShop,
            ],
            'last_message' => $conversation->latestMessage?->body,
            'unread_count' => (int) ($conversation->getAttribute('unread_count') ?? 0),
            'updated_at' => $conversation->updated_at?->toISOString(),
        ];

        if ($withMessages) {
            $payload['messages'] = $conversation->messages
                ->sortBy('created_at')
                ->map(fn (ChatMessage $message): array => $this->message($message))
                ->values();
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public function message(ChatMessage $message): array
    {
        $senderPayload = $message->sender->toArray();

        return [
            'id' => (string) $message->getKey(),
            'client_message_id' => (string) $message->client_message_id,
            'conversation_id' => (string) $message->chat_conversation_id,
            'body' => (string) $message->body,
            'reply_to' => $this->reply($message),
            'read_at' => $message->read_at?->toISOString(),
            'attachment' => $this->attachment($message),
            'product' => $this->product($message),
            'sender' => [
                'id' => (string) $message->sender->getKey(),
                'name' => (string) $message->sender->full_name,
                'avatar_url' => $senderPayload['avatar_url'],
            ],
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    public function counterpartId(ChatConversation $conversation, User $viewer): string
    {
        return (string) ($conversation->buyer_id === $viewer->getKey()
            ? $conversation->shop_owner_id
            : $conversation->buyer_id);
    }

    /** @return array<string, mixed>|null */
    private function reply(ChatMessage $message): ?array
    {
        $original = $message->replyTo;

        if ($original === null) {
            return null;
        }

        $senderPayload = $original->sender->toArray();

        return [
            'id' => (string) $original->getKey(),
            'body' => (string) $original->body,
            'sender' => [
                'id' => (string) $original->sender->getKey(),
                'name' => (string) $original->sender->full_name,
                'avatar_url' => $senderPayload['avatar_url'],
            ],
            'attachment' => $original->attachment_mime ? [
                'type' => $this->attachmentType($original),
                'name' => $original->attachment_name,
            ] : null,
            'product' => $original->shopProduct ? [
                'name' => (string) $original->shopProduct->name,
            ] : null,
        ];
    }

    private function attachmentType(ChatMessage $message): string
    {
        return match (true) {
            Str::startsWith((string) $message->attachment_mime, 'image/') => 'image',
            Str::startsWith((string) $message->attachment_mime, 'video/') => 'video',
            default => 'file',
        };
    }

    /** @return array<string, mixed>|null */
    private function attachment(ChatMessage $message): ?array
    {
        if (! $message->attachment_mime) {
            return null;
        }

        return [
            'url' => route('chats.attachments.show', $message),
            'name' => $message->attachment_name,
            'mime' => $message->attachment_mime,
            'size' => $message->attachment_size,
            'type' => $this->attachmentType($message),
        ];
    }

    /** @return array<string, mixed>|null */
    private function product(ChatMessage $message): ?array
    {
        $product = $message->shopProduct;

        if ($product === null) {
            return null;
        }

        $productPayload = $product->toArray();
        $variant = $message->shopProductVariant;

        return [
            'id' => (string) $product->getKey(),
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
        ];
    }
}
