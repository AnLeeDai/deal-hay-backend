<?php

use App\Enums\UserRoleEnum;
use App\Mail\AccountStatusChangedMail;
use App\Models\ChatConversation;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

it('lets administrators follow and react to shops as regular customers', function (): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    $shop = User::factory()->shopOwner()->create();

    $this->actingAs($administrator, 'sanctum')
        ->postJson('/api/shops/'.$shop->user_code.'/follow')->assertCreated();
    $this->assertDatabaseHas('shop_follows', [
        'follower_id' => $administrator->id, 'shop_owner_id' => $shop->id,
    ]);

    $this->putJson('/api/shops/'.$shop->user_code.'/reaction', ['reaction' => 'like'])->assertOk();
    $this->assertDatabaseHas('shop_reactions', [
        'customer_id' => $administrator->id, 'shop_owner_id' => $shop->id, 'reaction' => 'like',
    ]);
    $this->getJson('/api/shops/'.$shop->user_code)
        ->assertOk()->assertJsonPath('shop.viewer.can_interact', true)
        ->assertJsonPath('shop.viewer.is_following', true)->assertJsonPath('shop.viewer.reaction', 'like');

    $this->deleteJson('/api/shops/'.$shop->user_code.'/follow')->assertOk();
    $this->deleteJson('/api/shops/'.$shop->user_code.'/reaction')->assertOk();
    $this->assertDatabaseCount('shop_follows', 0);
    $this->assertDatabaseCount('shop_reactions', 0);
});

it('lets administrators start ordinary product conversations', function (): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();

    $response = $this->actingAs($administrator, 'sanctum')
        ->postJson('/api/shops/'.$shop->user_code.'/chat', ['product' => $product->id]);

    $response->assertCreated()->assertJsonPath('conversation.counterpart.is_shop', true);
    $this->assertDatabaseHas('chat_messages', [
        'chat_conversation_id' => $response->json('conversation.id'),
        'sender_id' => $administrator->id,
        'shop_product_id' => $product->id,
    ]);
});

it('opens admin conversations with customer and shop accounts using their correct identity', function (string $role, bool $active): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    $recipient = User::factory()->create([
        'role' => $role, 'is_active' => $active, 'full_name' => 'Người nhận hỗ trợ',
        'shop_name' => $role === UserRoleEnum::SHOP_OWNER ? 'Shop hỗ trợ' : null,
    ]);

    $response = $this->actingAs($administrator, 'sanctum')
        ->postJson('/api/admin/users/'.$recipient->id.'/chat');

    $response->assertCreated()
        ->assertJsonPath('conversation.counterpart.id', $recipient->id)
        ->assertJsonPath('conversation.counterpart.is_shop', $role === UserRoleEnum::SHOP_OWNER)
        ->assertJsonPath('conversation.counterpart.name', $role === UserRoleEnum::SHOP_OWNER ? 'Shop hỗ trợ' : 'Người nhận hỗ trợ');
    $this->assertDatabaseHas('chat_conversations', [
        'buyer_id' => $administrator->id, 'shop_owner_id' => $recipient->id,
    ]);
    $this->getJson('/api/messages/'.$response->json('conversation.slug'))
        ->assertOk()->assertJsonPath('selectedConversation.counterpart.id', $recipient->id)
        ->assertJsonPath('selectedConversation.counterpart.is_shop', $role === UserRoleEnum::SHOP_OWNER);
})->with([
    'customer' => [UserRoleEnum::CUSTOMER, true],
    'shop owner' => [UserRoleEnum::SHOP_OWNER, true],
    'locked customer' => [UserRoleEnum::CUSTOMER, false],
]);

it('reuses an existing conversation when the administrator selects the same user again', function (): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    $recipient = User::factory()->customer()->create();
    $conversation = ChatConversation::factory()->create([
        'buyer_id' => $administrator->id, 'shop_owner_id' => $recipient->id,
    ]);

    $this->actingAs($administrator, 'sanctum')
        ->postJson('/api/admin/users/'.$recipient->id.'/chat')
        ->assertOk()->assertJsonPath('conversation.id', $conversation->id);

    $this->assertDatabaseCount('chat_conversations', 1);
});

it('returns 403 when a nonadministrator tries to select an arbitrary user for chat', function (string $role): void {
    $actor = User::factory()->create(['role' => $role]);
    $recipient = User::factory()->customer()->create();

    $this->actingAs($actor, 'sanctum')
        ->postJson('/api/admin/users/'.$recipient->id.'/chat')->assertForbidden();

    $this->assertDatabaseCount('chat_conversations', 0);
})->with([UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER]);

it('returns 401 when a guest tries to start an admin conversation', function (): void {
    $recipient = User::factory()->customer()->create();

    $this->postJson('/api/admin/users/'.$recipient->id.'/chat')->assertUnauthorized();

    $this->assertDatabaseCount('chat_conversations', 0);
});

it('returns 403 when the administrator tries to chat with themselves', function (): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();

    $this->actingAs($administrator, 'sanctum')
        ->postJson('/api/admin/users/'.$administrator->id.'/chat')->assertForbidden();

    $this->assertDatabaseCount('chat_conversations', 0);
});

it('allows both participants to message and reply in an admin conversation', function (): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    $recipient = User::factory()->customer()->create();
    $conversation = ChatConversation::factory()->create([
        'buyer_id' => $administrator->id, 'shop_owner_id' => $recipient->id,
    ]);
    $messageId = (string) Str::uuid();

    $response = $this->actingAs($administrator, 'sanctum')
        ->withHeader('Idempotency-Key', $messageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $messageId, 'body' => 'Deal Hay có thể hỗ trợ gì cho bạn?',
        ])->assertCreated();

    $this->actingAs($recipient, 'sanctum')
        ->getJson('/api/messages/'.$conversation->slug)
        ->assertOk()->assertJsonPath('selectedConversation.counterpart.name', $administrator->full_name)
        ->assertJsonPath('selectedConversation.counterpart.is_shop', false);
    $replyId = (string) Str::uuid();
    $this->withHeader('Idempotency-Key', $replyId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $replyId, 'body' => 'Tôi cần hỗ trợ đơn hàng.',
            'reply_to_id' => $response->json('id'),
        ])->assertCreated();

    $this->assertDatabaseHas('chat_messages', [
        'chat_conversation_id' => $conversation->id,
        'sender_id' => $recipient->id, 'reply_to_id' => $response->json('id'),
    ]);
});

it('returns 403 when another account reads or writes an admin conversation', function (): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    $recipient = User::factory()->customer()->create();
    $outsider = User::factory()->customer()->create();
    $conversation = ChatConversation::factory()->create([
        'buyer_id' => $administrator->id, 'shop_owner_id' => $recipient->id,
    ]);
    $messageId = (string) Str::uuid();

    $this->actingAs($outsider, 'sanctum')
        ->getJson('/api/messages/'.$conversation->slug)->assertForbidden();
    $this->withHeader('Idempotency-Key', $messageId)
        ->postJson('/api/messages/'.$conversation->slug, [
            'client_message_id' => $messageId, 'body' => 'Tin nhắn trái phép',
        ])->assertForbidden();

    $this->assertDatabaseCount('chat_messages', 0);
});

it('locks a shop through the admin account action and hides it until unlocked', function (): void {
    $administrator = User::query()->where('role', UserRoleEnum::ADMINISTRATOR)->firstOrFail();
    $shop = User::factory()->shopOwner()->create();
    $shop->createToken('shop-session');
    Mail::fake([AccountStatusChangedMail::class]);

    $this->actingAs($administrator, 'sanctum')
        ->patchJson('/api/admin/users/'.$shop->id.'/toggle-lock', [
            'action' => 'lock', 'lock_reason' => 'Gian hàng cần được kiểm tra.',
        ])->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $shop->id, 'is_active' => false, 'lock_reason' => 'Gian hàng cần được kiểm tra.',
    ]);
    expect($shop->tokens()->count())->toBe(0);
    $this->getJson('/api/shops/'.$shop->user_code)->assertNotFound();

    $this->patchJson('/api/admin/users/'.$shop->id.'/toggle-lock', ['action' => 'unlock'])->assertOk();
    $this->getJson('/api/shops/'.$shop->user_code)->assertOk();
    $this->assertDatabaseHas('users', ['id' => $shop->id, 'is_active' => true, 'lock_reason' => null]);
    Mail::assertQueued(AccountStatusChangedMail::class, 2);
});
