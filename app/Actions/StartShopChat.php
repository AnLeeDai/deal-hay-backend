<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Events\ChatMessageSentEvent;
use App\Models\ChatConversation;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\ChatMessageIdempotencyService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StartShopChat
{
    private const string PRODUCT_MESSAGE = 'Tôi muốn hỏi về sản phẩm này.';

    public function __construct(
        private readonly ChatMessageIdempotencyService $idempotency,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{conversation: ChatConversation, replayed: bool}
     */
    public function handle(User $buyer, User $shop, array $data): array
    {
        abort_unless(
            in_array($buyer->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER, UserRoleEnum::ADMINISTRATOR], true)
            && (bool) $buyer->is_active
            && $shop->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shop->is_active
            && $buyer->getKey() !== $shop->getKey(),
            403,
        );

        $productId = $data['product'] ?? null;
        $variantId = filled($data['variant'] ?? null) ? (string) $data['variant'] : null;
        $providedClientMessageId = filled($data['client_message_id'] ?? null)
            ? (string) $data['client_message_id']
            : null;
        $clientMessageId = $providedClientMessageId ?: (string) Str::uuid();
        $product = $productId
            ? ShopProduct::query()
                ->whereBelongsTo($shop, 'shopOwner')
                ->where('is_active', true)
                ->findOrFail($productId)
            : null;
        $requestFingerprint = $this->idempotency->fingerprint([
            'body' => self::PRODUCT_MESSAGE,
            'product_id' => $product?->getKey(),
            'variant_id' => $variantId,
        ]);

        try {
            return DB::transaction(function () use ($buyer, $clientMessageId, $providedClientMessageId, $requestFingerprint, $shop, $product, $variantId): array {
                $conversation = ChatConversation::query()->firstOrCreate([
                    'buyer_id' => $buyer->getKey(),
                    'shop_owner_id' => $shop->getKey(),
                ]);

                if ($product === null) {
                    return ['conversation' => $conversation, 'replayed' => false];
                }

                if ($providedClientMessageId !== null) {
                    $existingMessage = $this->idempotency->find(
                        $conversation,
                        (string) $buyer->getKey(),
                        $clientMessageId,
                    );

                    if ($existingMessage !== null) {
                        $this->idempotency->assertMatches($existingMessage, $requestFingerprint);

                        return ['conversation' => $conversation, 'replayed' => true];
                    }
                }

                $messageData = [
                    'sender_id' => $buyer->getKey(),
                    'client_message_id' => $clientMessageId,
                    'request_fingerprint' => $requestFingerprint,
                    'shop_product_id' => $product->getKey(),
                    'body' => self::PRODUCT_MESSAGE,
                ];

                if ($variantId !== null) {
                    $messageData['shop_product_variant_id'] = $variantId;
                }

                $message = $conversation->messages()->create($messageData);
                $conversation->touch();
                $this->idempotency->loadForDelivery($message);
                broadcast(new ChatMessageSentEvent($message))->toOthers();

                return ['conversation' => $conversation, 'replayed' => false];
            }, attempts: 5);
        } catch (UniqueConstraintViolationException $exception) {
            $conversation = ChatConversation::query()
                ->where('buyer_id', $buyer->getKey())
                ->where('shop_owner_id', $shop->getKey())
                ->first();
            $message = $conversation !== null && $providedClientMessageId !== null
                ? $this->idempotency->find(
                    $conversation,
                    (string) $buyer->getKey(),
                    $clientMessageId,
                )
                : null;

            if ($conversation === null || $message === null) {
                throw $exception;
            }

            $this->idempotency->assertMatches($message, $requestFingerprint);

            return ['conversation' => $conversation, 'replayed' => true];
        }
    }
}
