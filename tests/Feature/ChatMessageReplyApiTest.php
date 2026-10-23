<?php

use App\Events\ChatMessageSentEvent;
use App\Events\ChatMessagesReadEvent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('stores a reply and broadcasts its original message summary', function (string $body): void {
    $conversation = ChatConversation::factory()->create();
    $original = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'sender_id' => $conversation->shop_owner_id,
        'body' => 'Bạn muốn chọn màu nào?',
    ]);
    $clientMessageId = (string) Str::uuid();
    Sanctum::actingAs($conversation->buyer);
    Queue::fake([BroadcastEvent::class]);
    $expectedSummary = [
        'id' => $original->getKey(),
        'body' => 'Bạn muốn chọn màu nào?',
        'sender' => [
            'id' => $conversation->shop_owner_id,
            'name' => $conversation->shopOwner->full_name,
            'avatar_url' => $conversation->shopOwner->toArray()['avatar_url'],
        ],
        'attachment' => null,
        'product' => null,
    ];

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId,
            'reply_to_id' => $original->getKey(),
            'body' => $body,
        ]);

    $response->assertCreated()->assertJsonPath('body', $body)->assertJsonPath('reply_to', $expectedSummary);
    $this->assertDatabaseHas('chat_messages', [
        'id' => $response->json('id'), 'reply_to_id' => $original->getKey(),
        'sender_id' => $conversation->buyer_id, 'body' => $body,
    ]);
    Queue::assertPushed(BroadcastEvent::class, fn (BroadcastEvent $job): bool => $job->event instanceof ChatMessageSentEvent
        && $job->event->broadcastWith()['reply_to'] === $expectedSummary);
})->with(['text' => 'Mình chọn màu xanh.', 'native Unicode emoji' => '👍🏽❤️👨‍👩‍👧‍👦']);

it('sends an image-only reply retaining the original attachment summary', function (): void {
    $disk = Storage::fake('r2_private');
    config()->set('filesystems.chat_media_disk', 'r2_private');
    $conversation = ChatConversation::factory()->create();
    $original = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'sender_id' => $conversation->shop_owner_id, 'body' => '[Hình ảnh]',
        'attachment_mime' => 'image/webp', 'attachment_name' => 'original.webp',
    ]);
    $clientMessageId = (string) Str::uuid();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->post('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId,
            'reply_to_id' => $original->getKey(),
            'attachment' => preparedImage('reply.webp'),
        ], ['Accept' => 'application/json']);

    $response->assertCreated()->assertJsonPath('attachment.type', 'image')
        ->assertJsonPath('reply_to.id', $original->getKey())
        ->assertJsonPath('reply_to.attachment', ['type' => 'image', 'name' => 'original.webp']);
    $message = ChatMessage::query()->findOrFail($response->json('id'));
    expect($message->reply_to_id)->toBe($original->getKey());
    $disk->assertExists($message->attachment_path);
    Event::assertDispatched(ChatMessageSentEvent::class, fn (ChatMessageSentEvent $event): bool => $event->message->is($message));
});

it('returns 422 without sending when the reply target is outside the conversation', function (): void {
    $conversation = ChatConversation::factory()->create();
    $foreignMessage = ChatMessage::factory()->create();
    $clientMessageId = (string) Str::uuid();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId, 'reply_to_id' => $foreignMessage->getKey(),
            'body' => 'Không được trích dẫn cuộc trò chuyện khác.',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['reply_to_id' => 'Tin nhắn được trả lời không tồn tại trong cuộc trò chuyện này.']);
    $this->assertDatabaseCount('chat_messages', 1);
    Event::assertNotDispatched(ChatMessageSentEvent::class);
});

it('returns 422 without sending for an invalid reply target', function (string $target, string $error): void {
    $conversation = ChatConversation::factory()->create();
    $clientMessageId = (string) Str::uuid();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId, 'reply_to_id' => $target, 'body' => 'Tin trả lời',
        ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['reply_to_id' => $error]);
    $this->assertDatabaseCount('chat_messages', 0);
    Event::assertNotDispatched(ChatMessageSentEvent::class);
})->with([
    'missing UUID' => ['00000000-0000-4000-8000-000000000000', 'Tin nhắn được trả lời không tồn tại trong cuộc trò chuyện này.'],
    'malformed ID' => ['not-a-uuid', 'Tin nhắn được trả lời không hợp lệ.'],
]);

it('returns 403 without sending when an outsider replies to a conversation', function (): void {
    $original = ChatMessage::factory()->create();
    $conversation = $original->conversation;
    $clientMessageId = (string) Str::uuid();
    Sanctum::actingAs(User::factory()->customer()->create());
    Event::fake([ChatMessageSentEvent::class]);

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId, 'reply_to_id' => $original->getKey(), 'body' => 'Tin trả lời',
        ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('chat_messages', 1);
    Event::assertNotDispatched(ChatMessageSentEvent::class);
});

it('loads reply summaries outside the visible history window without recursive replies', function (): void {
    $this->freezeTime();
    $conversation = ChatConversation::factory()->create();
    $product = ShopProduct::factory()->create(['shop_owner_id' => $conversation->shop_owner_id, 'name' => 'Áo màu xanh']);
    $ancestor = ChatMessage::factory()->for($conversation, 'conversation')->create(['created_at' => now()->subMinutes(40)]);
    $original = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'reply_to_id' => $ancestor->getKey(), 'shop_product_id' => $product->getKey(),
        'body' => 'Áo màu xanh còn hàng.', 'created_at' => now()->subMinutes(39),
    ]);
    $olderReply = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'reply_to_id' => $original->getKey(), 'created_at' => now()->subMinutes(38),
    ]);
    ChatMessage::factory()->count(22)->for($conversation, 'conversation')->create(['created_at' => now()->subMinutes(10)]);
    $newestReply = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'reply_to_id' => $original->getKey(), 'created_at' => now(),
    ]);
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessagesReadEvent::class]);

    $response = $this->getJson('/api/messages/'.$conversation->slug);

    $response->assertOk()->assertJsonPath('selectedConversation.messages_pagination.has_more', true);
    $messages = collect($response->json('selectedConversation.messages'));
    expect($messages->pluck('id'))->not->toContain($original->getKey());
    $summary = $messages->firstWhere('id', $newestReply->getKey())['reply_to'];
    expect($summary)->toHaveKeys(['id', 'body', 'sender', 'attachment', 'product'])->not->toHaveKey('reply_to');
    expect($summary['id'])->toBe($original->getKey());
    expect($summary['product'])->toBe(['name' => 'Áo màu xanh']);
    $cursor = $response->json('selectedConversation.messages_pagination.next_cursor');

    $history = $this->getJson('/api/messages/'.$conversation->slug.'/history?cursor='.urlencode($cursor));

    $history->assertOk();
    expect(collect($history->json('messages'))->firstWhere('id', $olderReply->getKey())['reply_to'])->toBe($summary);
    Event::assertNotDispatched(ChatMessagesReadEvent::class);
});

it('replays the same reply once and returns 409 when the reply target changes', function (): void {
    $conversation = ChatConversation::factory()->create();
    $originals = ChatMessage::factory()->count(2)->for($conversation, 'conversation')->create();
    $clientMessageId = (string) Str::uuid();
    $payload = ['client_message_id' => $clientMessageId, 'body' => 'Đồng ý', 'reply_to_id' => $originals[0]->getKey()];
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);
    $first = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$conversation->slug, $payload)->assertCreated();

    $this->postJson('/api/messages/'.$conversation->slug, $payload)
        ->assertOk()->assertJsonPath('id', $first->json('id'))->assertJsonPath('replayed', true)
        ->assertJsonPath('reply_to.id', $originals[0]->getKey());
    $this->postJson('/api/messages/'.$conversation->slug, [...$payload, 'reply_to_id' => $originals[1]->getKey()])
        ->assertConflict()->assertJsonPath('message', 'client_message_id đã được sử dụng cho một nội dung khác.');

    $this->assertDatabaseCount('chat_messages', 3);
    Event::assertDispatchedTimes(ChatMessageSentEvent::class, 1);
});

it('keeps a reply after its original message is deleted', function (): void {
    $conversation = ChatConversation::factory()->create();
    $original = ChatMessage::factory()->for($conversation, 'conversation')->create();
    $reply = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'reply_to_id' => $original->getKey(), 'body' => 'Vẫn giữ tin trả lời',
    ]);
    $original->delete();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessagesReadEvent::class]);

    $response = $this->getJson('/api/messages/'.$conversation->slug);

    $response->assertOk()->assertJsonPath('selectedConversation.messages.0.id', $reply->getKey())
        ->assertJsonPath('selectedConversation.messages.0.body', 'Vẫn giữ tin trả lời')
        ->assertJsonPath('selectedConversation.messages.0.reply_to', null);
    $this->assertDatabaseHas('chat_messages', ['id' => $reply->getKey(), 'reply_to_id' => null]);
    Event::assertNotDispatched(ChatMessagesReadEvent::class);
});

it('preserves legacy non-reply idempotency fingerprints', function (): void {
    $conversation = ChatConversation::factory()->create();
    $clientMessageId = (string) Str::uuid();
    $message = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'client_message_id' => $clientMessageId, 'body' => 'Tin trước khi có trả lời',
        'request_fingerprint' => hash('sha256', json_encode(['body' => 'Tin trước khi có trả lời', 'attachment_sha256' => null], JSON_THROW_ON_ERROR)),
    ]);
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);

    $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId, 'body' => 'Tin trước khi có trả lời', 'reply_to_id' => null,
        ])->assertOk()->assertJsonPath('id', $message->getKey())
        ->assertJsonPath('reply_to', null)->assertJsonPath('replayed', true);

    $this->assertDatabaseCount('chat_messages', 1);
    Event::assertNotDispatched(ChatMessageSentEvent::class);
});

it('loads a page of reply summaries without lazy database queries', function (): void {
    $this->freezeTime();
    $conversation = ChatConversation::factory()->create();
    $originals = ChatMessage::factory()->count(20)->for($conversation, 'conversation')->create([
        'sender_id' => $conversation->shop_owner_id,
        'created_at' => now()->subHour(),
    ]);
    foreach ($originals as $original) {
        ChatMessage::factory()->for($conversation, 'conversation')->create([
            'reply_to_id' => $original->getKey(),
            'created_at' => now(),
        ]);
    }
    Sanctum::actingAs($conversation->buyer);
    Model::preventLazyLoading();

    try {
        $response = $this->getJson('/api/messages/'.$conversation->slug.'/history');
    } finally {
        Model::preventLazyLoading(false);
    }

    $response->assertOk()->assertJsonCount(20, 'messages');
    expect(collect($response->json('messages'))->pluck('reply_to.id')->sort()->values()->all())
        ->toBe(collect($originals->modelKeys())->sort()->values()->all());
});

it('summarizes the original video or file without exposing its storage path', function (string $mime, string $type, string $name): void {
    $conversation = ChatConversation::factory()->create();
    $original = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'attachment_mime' => $mime,
        'attachment_name' => $name,
        'attachment_path' => 'chat-media/private-path/original',
    ]);
    $clientMessageId = (string) Str::uuid();
    Sanctum::actingAs($conversation->buyer);
    Event::fake([ChatMessageSentEvent::class]);

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $clientMessageId,
            'reply_to_id' => $original->getKey(),
            'body' => 'Đã xem tệp',
        ]);

    $response->assertCreated()->assertJsonPath('reply_to.attachment', ['type' => $type, 'name' => $name]);
    $this->assertDatabaseHas('chat_messages', ['id' => $response->json('id'), 'reply_to_id' => $original->getKey()]);
    Event::assertDispatched(ChatMessageSentEvent::class);
})->with([
    'video' => ['video/mp4', 'video', 'demo.mp4'],
    'document' => ['application/pdf', 'file', 'guide.pdf'],
]);

it('returns 401 without saving an unauthenticated reply', function (): void {
    $original = ChatMessage::factory()->create();
    $clientMessageId = (string) Str::uuid();
    Event::fake([ChatMessageSentEvent::class]);

    $response = $this->withHeader('Idempotency-Key', $clientMessageId)
        ->postJson('/api/messages/'.$original->conversation->slug, [
            'client_message_id' => $clientMessageId,
            'reply_to_id' => $original->getKey(),
            'body' => 'Tin trả lời',
        ]);

    $response->assertUnauthorized();
    $this->assertDatabaseCount('chat_messages', 1);
    Event::assertNotDispatched(ChatMessageSentEvent::class);
});

it('rolls back reply support without deleting conversation messages', function (): void {
    $conversation = ChatConversation::factory()->create();
    $original = ChatMessage::factory()->for($conversation, 'conversation')->create();
    $reply = ChatMessage::factory()->for($conversation, 'conversation')->create([
        'reply_to_id' => $original->getKey(), 'body' => 'Tin nhắn được giữ lại',
    ]);
    $migration = require database_path('migrations/2026_10_15_112011_add_reply_to_id_to_chat_messages_table.php');

    $migration->down();
    $migration->up();

    $this->assertDatabaseCount('chat_messages', 2);
    $this->assertDatabaseHas('chat_messages', [
        'id' => $reply->getKey(), 'body' => 'Tin nhắn được giữ lại', 'reply_to_id' => null,
    ]);
});
