<?php

use App\Models\ProductDiscountCode;
use App\Models\RewardedTaskConversion;
use App\Models\RewardedTaskSession;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function retiredRewardedTaskUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/rewarded-tasks';
}

function historicalRewardedTaskSession(User $buyer, ShopProduct $product): RewardedTaskSession
{
    return RewardedTaskSession::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'provider' => 'adgem', 'app_id' => '12345',
        'player_id' => hash('sha256', 'historical-player:'.$buyer->getKey()),
        'binding_token' => str_repeat('a', 64), 'reward_amount' => 1000,
        'expires_at' => now()->addDays(90),
    ]);
}

it('returns 404 for retired task configuration, creation and lookup without minting coupons', function (bool $authenticated): void {
    $product = ShopProduct::factory()->create();
    if ($authenticated) {
        Sanctum::actingAs(User::factory()->customer()->create());
    }

    $this->getJson(retiredRewardedTaskUrl($product))->assertNotFound();
    $this->postJson(retiredRewardedTaskUrl($product), ['completed' => true])->assertNotFound();
    $this->getJson(retiredRewardedTaskUrl($product).'/'.Str::uuid())->assertNotFound();

    $this->assertDatabaseCount('rewarded_task_sessions', 0);
    $this->assertDatabaseCount('rewarded_task_conversions', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
})->with(['guest' => false, 'customer' => true]);

it('returns 404 for signed legacy postbacks even when old provider settings are enabled', function (): void {
    $this->freezeTime();
    config()->set('rewarded_tasks', [
        'provider' => 'adgem', 'reward_amount' => 1000, 'session_days' => 90, 'coupon_days' => 30,
        'adgem' => ['enabled' => true, 'reward_mapping_confirmed' => true, 'app_id' => '12345', 'postback_key' => 'server-only-test-secret', 'postback_ips' => []],
    ]);
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create();
    $session = historicalRewardedTaskSession($buyer, $product);
    $body = json_encode([
        'request_id' => (string) Str::uuid(), 'timestamp' => now()->timestamp,
        'data' => [
            'app_id' => '12345', 'player_id' => $session->player_id,
            'c1' => $session->getKey(), 'c2' => $session->binding_token,
            'conversion_id' => 'legacy-conversion', 'goal_id' => 'legacy-goal',
            'conversion_type' => 'reward', 'payout' => '0.50000000', 'amount' => 500,
        ],
    ], JSON_THROW_ON_ERROR);
    Sanctum::actingAs($buyer);

    $this->postJson(retiredRewardedTaskUrl($product))->assertNotFound();
    $this->getJson(retiredRewardedTaskUrl($product).'/'.$session->getKey())->assertNotFound();
    $this->postJson(retiredRewardedTaskUrl($product).'/'.$session->getKey().'/complete', ['completed' => true])->assertNotFound();
    $this->call('POST', '/api/postbacks/adgem/v3', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_SIGNATURE' => hash_hmac('sha256', $body, 'server-only-test-secret'),
    ], $body)->assertNotFound();

    $this->assertDatabaseCount('rewarded_task_sessions', 1);
    $this->assertDatabaseCount('rewarded_task_conversions', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
    $this->assertModelExists($session);
});

it('still redeems an existing confirmed task coupon once and replays its order idempotently', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 20000, 'old_price' => null, 'stock' => 10]);
    $session = historicalRewardedTaskSession($buyer, $product);
    $code = ProductDiscountCode::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'code' => str_repeat('B', 32), 'original_price' => 20000, 'sale_price' => 19000,
        'reward_type' => 'fixed', 'reward_value' => 1000, 'saved_at' => now(), 'expires_at' => now()->addDays(30),
    ]);
    $conversion = RewardedTaskConversion::query()->create([
        'rewarded_task_session_id' => $session->getKey(), 'provider' => 'adgem', 'app_id' => '12345',
        'conversion_key' => hash('sha256', 'historical-conversion'), 'request_id' => 'historical-request',
        'conversion_id' => 'historical-conversion', 'payout' => '0.50000000', 'provider_amount' => 500,
        'product_discount_code_id' => $code->getKey(),
    ]);
    $orderUrl = '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders';
    $payload = [
        'customer_name' => 'Người nhận thử nghiệm', 'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        'quantity' => 2, 'discount_codes' => [$code->code],
    ];
    $idempotencyKey = (string) Str::uuid();
    Sanctum::actingAs($buyer);

    $response = $this->withHeader('Idempotency-Key', $idempotencyKey)->postJson($orderUrl, $payload)
        ->assertCreated()->assertJsonPath('order.discount_amount', 1000)->assertJsonPath('order.total', 39000);
    $this->withHeader('Idempotency-Key', $idempotencyKey)->postJson($orderUrl, $payload)
        ->assertCreated()->assertJsonPath('order.id', $response->json('order.id'));
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($orderUrl, $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 1);
    expect($code->fresh()->used_at)->not->toBeNull();
    expect($product->fresh()->stock)->toBe(8);
    $this->assertModelExists($conversion);
});

it('rejects a saved code with no historical view or verified provider conversion at checkout', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 20000, 'stock' => 10]);
    $code = ProductDiscountCode::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'code' => str_repeat('A', 32), 'original_price' => 20000, 'sale_price' => 19000,
        'reward_type' => 'fixed', 'reward_value' => 1000, 'saved_at' => now(), 'expires_at' => now()->addDays(30),
    ]);
    Sanctum::actingAs($buyer);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson('/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders', [
            'customer_name' => 'Người nhận thử nghiệm', 'customer_phone' => '0901234567',
            'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
            'quantity' => 1, 'discount_codes' => [$code->code],
        ])->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($code->fresh()->used_at)->toBeNull();
});
