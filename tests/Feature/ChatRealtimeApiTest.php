<?php

use App\Events\ChatMessageSentEvent;
use App\Events\ChatMessagesReadEvent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('returns browser-safe realtime configuration to authenticated users', function (): void {
    config()->set('reverb.apps.apps.0.key', 'public-test-key');
    config()->set('reverb.apps.apps.0.secret', 'never-expose-this');
    config()->set('reverb.public', [
        'host' => 'realtime.example.test',
        'port' => 443,
        'scheme' => 'https',
    ]);

    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/realtime/config')
        ->assertOk()
        ->assertExactJson([
            'broadcaster' => 'reverb',
            'key' => 'public-test-key',
            'host' => 'realtime.example.test',
            'port' => 443,
            'scheme' => 'https',
            'heartbeat_interval_seconds' => 45,
        ])
        ->assertJsonMissing(['secret' => 'never-expose-this']);
});

it('rejects realtime configuration requests without authentication', function (): void {
    $this->getJson('/api/realtime/config')->assertUnauthorized();
});

it('authorizes only conversation members for private and presence channels', function (): void {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'public-test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app');
    Broadcast::purge('reverb');
    require base_path('routes/channels.php');

    $conversation = ChatConversation::factory()->create();
    $buyer = $conversation->buyer;
    $outsider = User::factory()->customer()->create();

    Sanctum::actingAs($buyer);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-chat.'.$conversation->getKey(),
    ])->assertOk()->assertJsonStructure(['auth']);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'presence-chat-presence.'.$conversation->getKey(),
    ])->assertOk()->assertJsonStructure(['auth', 'channel_data']);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-user-chat.'.$buyer->getKey(),
    ])->assertOk()->assertJsonStructure(['auth']);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-user-chat.'.$outsider->getKey(),
    ])->assertForbidden();

    Sanctum::actingAs($outsider);

    $this->postJson('/broadcasting/auth', [
        'socket_id' => '123.456',
        'channel_name' => 'private-chat.'.$conversation->getKey(),
    ])->assertForbidden();
});

it('records presence only for conversations the user belongs to', function (): void {
    $conversation = ChatConversation::factory()->create();
    $outsider = User::factory()->customer()->create();

    Sanctum::actingAs($conversation->buyer);

    $this->postJson('/api/presence/heartbeat', [
        'conversation_id' => $conversation->getKey(),
    ])->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['expires_in', 'server_time']);

    Sanctum::actingAs($outsider);

    $this->postJson('/api/presence/heartbeat', [
        'conversation_id' => $conversation->getKey(),
    ])->assertForbidden();
});

it('queues sent and read events with the originating socket excluded', function (): void {
    config()->set('queue.connections.database_outbox.queue', 'test-realtime');
    Queue::fake([BroadcastEvent::class]);

    $conversation = ChatConversation::factory()->create();
    $buyer = $conversation->buyer;
    $shopOwner = $conversation->shopOwner;
    Sanctum::actingAs($buyer);

    $clientMessageId = (string) Str::uuid();

    $this->withHeaders([
        'Idempotency-Key' => $clientMessageId,
        'X-Socket-ID' => '123.456',
    ])->postJson('/api/messages/'.$conversation->slug, [
        'client_message_id' => $clientMessageId,
        'body' => 'Tin nhắn realtime',
    ])->assertCreated()
        ->assertJsonPath('client_message_id', $clientMessageId)
        ->assertJsonPath('body', 'Tin nhắn realtime');

    Queue::assertPushedOn('test-realtime', BroadcastEvent::class, fn (BroadcastEvent $job): bool => $job->event instanceof ChatMessageSentEvent
        && $job->event->socket === '123.456');

    Queue::fake([BroadcastEvent::class]);

    $message = ChatMessage::factory()->create([
        'chat_conversation_id' => $conversation->getKey(),
        'sender_id' => $buyer->getKey(),
    ]);
    Sanctum::actingAs($shopOwner);

    $this->withHeader('X-Socket-ID', '789.012')
        ->patchJson('/api/messages/'.$conversation->slug.'/read')
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect($message->refresh()->read_at)->not->toBeNull();

    Queue::assertPushedOn('test-realtime', BroadcastEvent::class, fn (BroadcastEvent $job): bool => $job->event instanceof ChatMessagesReadEvent
        && $job->event->socket === '789.012');
});
