<?php

namespace App\Actions;

use App\Events\ChatMessagesReadEvent;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkChatConversationRead
{
    private const int EVENT_CHUNK_SIZE = 100;

    public function handle(ChatConversation $conversation, User $viewer): void
    {
        DB::transaction(function () use ($conversation, $viewer): void {
            $readAt = now();
            $readThroughAt = null;
            $updatedMessages = 0;

            do {
                $messages = $conversation->messages()
                    ->select(['id', 'created_at'])
                    ->where('sender_id', '!=', $viewer->getKey())
                    ->whereNull('read_at')
                    ->oldest('created_at')
                    ->orderBy('id')
                    ->limit(self::EVENT_CHUNK_SIZE)
                    ->lockForUpdate()
                    ->get();

                if ($messages->isEmpty()) {
                    break;
                }

                $messageIds = $messages->modelKeys();
                $updatedMessages += $conversation->messages()
                    ->whereKey($messageIds)
                    ->whereNull('read_at')
                    ->update(['read_at' => $readAt]);
                $readThroughAt = $messages->max('created_at');

                broadcast(new ChatMessagesReadEvent(
                    conversationId: (string) $conversation->getKey(),
                    readerId: (string) $viewer->getKey(),
                    messageIds: array_map('strval', $messageIds),
                    readAt: $readAt->toISOString(),
                    readThroughAt: $readThroughAt->toISOString(),
                    participantIds: [
                        (string) $conversation->buyer_id,
                        (string) $conversation->shop_owner_id,
                    ],
                ))->toOthers();
            } while ($messages->count() === self::EVENT_CHUNK_SIZE);

            if ($updatedMessages === 0 || $readThroughAt === null) {
                return;
            }

            $watermarkColumn = $conversation->buyer_id === $viewer->getKey()
                ? 'buyer_read_at'
                : 'shop_owner_read_at';
            $conversation->forceFill([$watermarkColumn => $readThroughAt])->save();
        }, attempts: 5);
    }
}
