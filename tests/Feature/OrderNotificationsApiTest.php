<?php

use App\Actions\NotifyShopOrder;
use App\Events\UserNotificationCreatedEvent;
use App\Events\UserNotificationsReadEvent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('persists and queues buyer and shop order notifications exactly once on checkout retries', function (): void {
    config(['broadcasting.default' => 'reverb']);
    Queue::fake([BroadcastEvent::class]);
    $buyer = User::factory()->customer()->create();
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 5]);
    Sanctum::actingAs($buyer);
    $key = (string) Str::uuid();
    $url = '/api/shops/'.$shop->user_code.'/products/'.$product->slug.'/orders';
    $payload = [
        'customer_name' => 'Nguyễn Văn An', 'customer_phone' => '0901234567',
        'shipping_address' => '12 Phố Huế', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        'quantity' => 1,
    ];
    $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)->assertCreated();
    $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)->assertCreated();

    $this->assertDatabaseCount('notifications', 2);
    expect($shop->notifications()->sole()->data)->toMatchArray([
        'title' => 'Có đơn hàng mới', 'category' => 'order.created',
        'action_url' => '/shop-manage?tab=orders',
    ]);
    expect($buyer->notifications()->sole()->data)->toMatchArray([
        'title' => 'Đặt hàng thành công', 'category' => 'order.created',
        'order_id' => ShopOrder::query()->sole()->id,
    ]);
    Queue::assertPushed(BroadcastEvent::class, 2);
    Queue::assertPushedOn('realtime', BroadcastEvent::class, fn (BroadcastEvent $job): bool => $job->event instanceof UserNotificationCreatedEvent
        && (string) $job->event->broadcastOn()[0] === 'private-user-notifications.'.$shop->id);
});

it('notifies both participants after a valid status change and ignores rejected transitions', function (): void {
    config(['broadcasting.default' => 'reverb']);
    Queue::fake([BroadcastEvent::class]);
    $buyer = User::factory()->customer()->create();
    $shop = User::factory()->shopOwner()->create();
    $order = ShopOrder::factory()->for($shop, 'shopOwner')->create(['customer_id' => $buyer->id]);
    Sanctum::actingAs($shop);

    $this->patchJson('/api/private/shop/orders/'.$order->id.'/status', ['status' => 'confirmed'])->assertOk();
    $this->patchJson('/api/private/shop/orders/'.$order->id.'/status', ['status' => 'pending'])->assertUnprocessable();

    $this->assertDatabaseCount('notifications', 2);
    expect($buyer->notifications()->sole()->data)->toMatchArray(['status' => 'confirmed', 'category' => 'order.status_updated']);
    Queue::assertPushed(BroadcastEvent::class, 2);
});

it('commits notification publication jobs atomically with the notification records', function (): void {
    $order = ShopOrder::factory()->create();
    $originalJobs = DB::table('jobs')->count();
    DB::beginTransaction();
    try {
        app(NotifyShopOrder::class)->created($order);
        expect($order->shopOwner->notifications()->count())->toBe(1);
        expect(DB::table('jobs')->count())->toBeGreaterThan($originalJobs);
    } finally {
        DB::rollBack();
    }

    expect($order->shopOwner->notifications()->count())->toBe(0);
    expect(DB::table('jobs')->count())->toBe($originalJobs);
});

it('marks only the displayed users notifications read and synchronizes other tabs once', function (): void {
    config(['broadcasting.default' => 'reverb']);
    Queue::fake([BroadcastEvent::class]);
    $buyer = User::factory()->customer()->create();
    $order = ShopOrder::factory()->create(['customer_id' => $buyer->id]);
    app(NotifyShopOrder::class)->created($order);
    $visible = $buyer->notifications()->sole();
    app(NotifyShopOrder::class)->statusUpdated($order);
    $notYetSeen = $buyer->notifications()->whereKeyNot($visible->id)->sole();
    $shopNotification = $order->shopOwner->notifications()->firstOrFail();
    Queue::fake([BroadcastEvent::class]);
    Sanctum::actingAs($buyer);

    $ids = [$visible->id, $shopNotification->id];
    $this->patchJson('/api/notifications/read-visible', ['notification_ids' => $ids])->assertOk();
    $this->patchJson('/api/notifications/read-visible', ['notification_ids' => $ids])->assertOk();

    expect($visible->fresh()->read_at)->not->toBeNull();
    expect($notYetSeen->fresh()->read_at)->toBeNull();
    expect($shopNotification->fresh()->read_at)->toBeNull();
    Queue::assertPushed(BroadcastEvent::class, 1);
    Queue::assertPushedOn('realtime', BroadcastEvent::class, fn (BroadcastEvent $job): bool => $job->event instanceof UserNotificationsReadEvent
        && $job->event->notificationIds === [$visible->id]
        && (string) $job->event->broadcastOn()[0] === 'private-user-notifications.'.$buyer->id);
    $this->patchJson('/api/notifications/'.$shopNotification->id.'/read')->assertForbidden();
});

it('validates visible notification batches and requires authentication', function (): void {
    $this->patchJson('/api/notifications/read-visible', ['notification_ids' => [(string) Str::uuid()]])->assertUnauthorized();
    Sanctum::actingAs(User::factory()->customer()->create());
    $this->patchJson('/api/notifications/read-visible', ['notification_ids' => []])->assertUnprocessable();
    $this->patchJson('/api/notifications/read-visible', ['notification_ids' => ['invalid']])->assertUnprocessable();
    $this->patchJson('/api/notifications/read-visible', ['notification_ids' => array_fill(0, 51, (string) Str::uuid())])->assertUnprocessable();
});

it('keeps unread messages when a conversation is fetched before it becomes visible', function (): void {
    $conversation = ChatConversation::factory()->create();
    $message = ChatMessage::factory()->create([
        'chat_conversation_id' => $conversation->id,
        'sender_id' => $conversation->shop_owner_id,
    ]);
    Sanctum::actingAs($conversation->buyer);
    $this->getJson('/api/messages/'.$conversation->slug)
        ->assertOk()->assertJsonPath('selectedConversation.unread_count', 1);
    expect($message->fresh()->read_at)->toBeNull();
    $this->patchJson('/api/messages/'.$conversation->slug.'/read')->assertOk();
    expect($message->fresh()->read_at)->not->toBeNull();
});
