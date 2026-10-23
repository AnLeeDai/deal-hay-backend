<?php

namespace App\Actions;

use App\Models\ChatConversation;
use App\Models\User;
use App\Services\ChatPayloadService;
use App\Services\UserPresenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class GetChatPage
{
    private const int INITIAL_MESSAGES_PER_PAGE = 20;

    private const int MAX_MESSAGES_PER_PAGE = 100;

    public function __construct(
        private readonly ChatPayloadService $payloads,
        private readonly UserPresenceService $presence,
    ) {}

    /** @return array{conversations: Collection<int, array<string, mixed>>, selectedConversation: array<string, mixed>|null} */
    public function handle(User $user, ?ChatConversation $selected = null, ?int $messageLimit = null): array
    {
        $conversations = $this->conversations($user);
        $messages = null;

        if ($selected !== null) {
            $selected->setAttribute(
                'unread_count',
                $conversations->firstWhere('id', (string) $selected->getKey())['unread_count'] ?? 0,
            );
            $messages = $this->messageQuery($selected)->cursorPaginate(
                $this->messageLimit($messageLimit),
            );
            $selected->load([
                'buyer:id,role,full_name,avatar_url,shop_name,shop_avatar_url',
                'shopOwner:id,role,full_name,avatar_url,shop_name,shop_avatar_url',
                'latestMessage' => fn ($query) => $query->select([
                    'chat_messages.id',
                    'chat_messages.chat_conversation_id',
                    'chat_messages.sender_id',
                    'chat_messages.body',
                    'chat_messages.created_at',
                ]),
            ]);
            $selected->setRelation('messages', $messages->getCollection()->reverse()->values());
        }

        $selectedPayload = $selected
            ? $this->payloads->conversation(
                $selected,
                $user,
                true,
                $this->presence->isOnline($this->payloads->counterpartId($selected, $user)),
            )
            : null;

        if ($selectedPayload !== null && $messages !== null) {
            $selectedPayload['messages_pagination'] = [
                'has_more' => $messages->hasMorePages(),
                'next_cursor' => $messages->nextCursor()?->encode(),
            ];
        }

        return [
            'conversations' => $conversations,
            'selectedConversation' => $selectedPayload,
        ];
    }

    /** @return array{messages: list<array<string, mixed>>, has_more: bool, next_cursor: string|null} */
    public function olderMessages(
        User $user,
        ChatConversation $conversation,
        ?string $cursor,
        ?int $messageLimit = null,
    ): array {
        $messages = $this->messageQuery($conversation)->cursorPaginate(
            $this->messageLimit($messageLimit),
            ['*'],
            'cursor',
            $cursor,
        );

        return [
            'messages' => $messages->getCollection()
                ->reverse()
                ->values()
                ->map(fn ($message): array => $this->payloads->message($message))
                ->all(),
            'has_more' => $messages->hasMorePages(),
            'next_cursor' => $messages->nextCursor()?->encode(),
        ];
    }

    private function messageLimit(?int $messageLimit): int
    {
        return min(
            self::MAX_MESSAGES_PER_PAGE,
            max(self::INITIAL_MESSAGES_PER_PAGE, $messageLimit ?? self::INITIAL_MESSAGES_PER_PAGE),
        );
    }

    private function messageQuery(ChatConversation $conversation): HasMany
    {
        return $conversation->messages()
            ->select([
                'id',
                'chat_conversation_id',
                'sender_id',
                'client_message_id',
                'reply_to_id',
                'shop_product_id',
                'shop_product_variant_id',
                'body',
                'read_at',
                'attachment_disk',
                'attachment_path',
                'attachment_name',
                'attachment_mime',
                'attachment_size',
                'created_at',
            ])
            ->with([
                'sender:id,full_name,avatar_url',
                'shopProduct:id,shop_owner_id,slug,name,sku,price,stock,has_variants,image_url,is_active',
                'shopProduct.shopOwner:id,user_code,is_active',
                'shopProductVariant.optionValues.option',
                'replyTo.sender:id,full_name,avatar_url',
                'replyTo.shopProduct:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function conversations(User $user): Collection
    {
        $conversations = ChatConversation::query()
            ->where(fn (Builder $query) => $query
                ->where('buyer_id', $user->getKey())
                ->orWhere('shop_owner_id', $user->getKey()))
            ->with([
                'buyer:id,role,full_name,avatar_url,shop_name,shop_avatar_url',
                'shopOwner:id,role,full_name,avatar_url,shop_name,shop_avatar_url',
                'latestMessage' => fn ($query) => $query->select([
                    'chat_messages.id',
                    'chat_messages.chat_conversation_id',
                    'chat_messages.sender_id',
                    'chat_messages.body',
                    'chat_messages.created_at',
                ]),
            ])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $query
                ->where('sender_id', '!=', $user->getKey())
                ->whereNull('read_at')])
            ->latest('updated_at')
            ->get();

        $onlineUserIds = $this->presence->onlineUserIds(
            $conversations->map(
                fn (ChatConversation $conversation): string => $this->payloads
                    ->counterpartId($conversation, $user),
            ),
        );

        return $conversations->map(fn (ChatConversation $conversation): array => $this->payloads
            ->conversation(
                $conversation,
                $user,
                counterpartIsOnline: $onlineUserIds->has(
                    $this->payloads->counterpartId($conversation, $user),
                ),
            ));
    }
}
