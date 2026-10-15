<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ChatMessageIdempotencyService
{
    private const array DELIVERY_RELATIONS = [
        'sender',
        'conversation',
        'shopProduct.shopOwner',
        'shopProductVariant.optionValues.option',
        'replyTo.sender',
        'replyTo.shopProduct',
    ];

    /** @param array<string, mixed> $payload */
    public function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function find(
        ChatConversation $conversation,
        string $senderId,
        string $clientMessageId,
    ): ?ChatMessage {
        return $conversation->messages()
            ->where('sender_id', $senderId)
            ->where('client_message_id', $clientMessageId)
            ->with(self::DELIVERY_RELATIONS)
            ->first();
    }

    public function assertMatches(ChatMessage $message, string $requestFingerprint): void
    {
        if (
            $message->request_fingerprint !== null
            && ! hash_equals($message->request_fingerprint, $requestFingerprint)
        ) {
            throw new ConflictHttpException(
                'client_message_id đã được sử dụng cho một nội dung khác.',
            );
        }
    }

    public function loadForDelivery(ChatMessage $message): ChatMessage
    {
        return $message->load(self::DELIVERY_RELATIONS);
    }
}
