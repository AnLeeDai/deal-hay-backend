<?php

use App\Enums\UserRoleEnum;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('registers a customer and issues a sanctum token', function (): void {
    $response = $this->postJson('/api/auth/register', [
        'email' => 'new.customer@example.com',
        'password' => 'Production!123',
        'password_confirmation' => 'Production!123',
        'accept_terms' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'Đăng ký thành công.')
        ->assertJsonPath('user.email', 'new.customer@example.com')
        ->assertJsonStructure(['token', 'user']);

    $user = User::query()->where('email', 'new.customer@example.com')->sole();

    expect($user->role)->toBe(UserRoleEnum::CUSTOMER)
        ->and($user->tokens()->count())->toBe(1);
});

it('logs in with a case insensitive email and returns the current user', function (): void {
    $user = User::factory()->customer()->create([
        'email' => 'case.customer@example.com',
        'password' => 'Production!123',
    ]);

    $login = $this->postJson('/api/auth/login', [
        'email' => 'CASE.CUSTOMER@EXAMPLE.COM',
        'password' => 'Production!123',
    ]);

    $token = $login->json('token');

    $login->assertOk()
        ->assertJsonPath('message', 'Đăng nhập thành công.')
        ->assertJsonPath('user.id', $user->getKey());

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->getKey())
        ->assertJsonPath('meta.unread_messages_count', 0)
        ->assertJsonPath('meta.unread_notifications_count', 0);
});

it('returns unread message and notification counts scoped to the current user', function (): void {
    $user = User::factory()->customer()->create();
    $shopOwner = User::factory()->shopOwner()->create();
    $conversation = ChatConversation::factory()->create([
        'buyer_id' => $user->getKey(),
        'shop_owner_id' => $shopOwner->getKey(),
    ]);

    ChatMessage::factory()->create([
        'chat_conversation_id' => $conversation->getKey(),
        'sender_id' => $shopOwner->getKey(),
    ]);
    ChatMessage::factory()->create([
        'chat_conversation_id' => $conversation->getKey(),
        'sender_id' => $user->getKey(),
    ]);
    ChatMessage::factory()->create([
        'chat_conversation_id' => $conversation->getKey(),
        'sender_id' => $shopOwner->getKey(),
        'read_at' => now(),
    ]);

    $outsiderConversation = ChatConversation::factory()->create();
    ChatMessage::factory()->create([
        'chat_conversation_id' => $outsiderConversation->getKey(),
        'sender_id' => $outsiderConversation->shop_owner_id,
    ]);

    $unreadNotification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test-unread-notification',
        'data' => [],
    ]);
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test-read-notification',
        'data' => [],
        'read_at' => now(),
    ]);
    $shopOwner->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test-outsider-notification',
        'data' => [],
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->getKey())
        ->assertJsonPath('meta.unread_messages_count', 1)
        ->assertJsonPath('meta.unread_notifications_count', 1);

    $this->patchJson('/api/messages/'.$conversation->slug.'/read')
        ->assertOk();
    $this->patchJson('/api/notifications/'.$unreadNotification->getKey().'/read')
        ->assertOk();

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('meta.unread_messages_count', 0)
        ->assertJsonPath('meta.unread_notifications_count', 0);
});

it('revokes the current token on logout', function (): void {
    $user = User::factory()->customer()->create([
        'password' => 'Production!123',
    ]);

    $token = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'Production!123',
    ])->json('token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Đăng xuất thành công.');

    expect($user->refresh()->tokens()->count())->toBe(0);
});

it('returns json 401 for protected routes without a token', function (): void {
    $this->getJson('/api/auth/me')
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);
});
