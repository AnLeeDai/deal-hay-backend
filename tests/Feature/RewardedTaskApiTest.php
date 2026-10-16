<?php

use App\Models\ProductDiscountCode;
use App\Models\RewardedTaskConversion;
use App\Models\RewardedTaskSession;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config()->set('rewarded_tasks', [
        'provider' => 'adgem', 'reward_amount' => 1000, 'session_days' => 90, 'coupon_days' => 30,
        'adgem' => ['enabled' => true, 'reward_mapping_confirmed' => true, 'app_id' => '12345', 'postback_key' => 'server-only-test-secret', 'postback_ips' => []],
    ]);
});

function rewardedTaskUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/rewarded-tasks';
}

function startRewardedTask(User $buyer, ShopProduct $product): RewardedTaskSession
{
    Sanctum::actingAs($buyer);
    $id = test()->postJson(rewardedTaskUrl($product))->assertCreated()->json('task.id');

    return RewardedTaskSession::query()->findOrFail($id);
}

/** @return array<string, mixed> */
function rewardedPostback(RewardedTaskSession $session, array $data = []): array
{
    return [
        'request_id' => (string) Str::uuid(),
        'timestamp' => now()->timestamp,
        'data' => [
            'app_id' => '12345', 'player_id' => $session->player_id,
            'c1' => $session->getKey(), 'c2' => $session->binding_token,
            'conversion_id' => 'provider-conversion-1', 'goal_id' => 'goal-1',
            'campaign_id' => 'campaign-1', 'offer_id' => 'offer-1',
            'payout' => '0.50000000', 'amount' => 500, 'conversion_type' => 'reward',
            ...$data,
        ],
    ];
}

function sendRewardedPostback(array $payload, ?string $signature = null): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/api/postbacks/adgem/v3', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_SIGNATURE' => $signature ?? hash_hmac('sha256', $body, 'server-only-test-secret'),
    ], $body);
}

it('keeps the current Adsterra account unavailable and never mints a coupon from a SmartLink claim', function (): void {
    config()->set('rewarded_tasks.provider', 'adsterra');
    $product = ShopProduct::factory()->create();

    $this->getJson(rewardedTaskUrl($product))->assertOk()
        ->assertJsonPath('enabled', false)->assertJsonPath('provider', 'adsterra')
        ->assertJsonPath('reward_amount', 1000)->assertJsonStructure(['reason']);
    Sanctum::actingAs(User::factory()->customer()->create());
    $this->postJson(rewardedTaskUrl($product))->assertServiceUnavailable();
    $this->postJson('/api/postbacks/adgem/v3', ['completed' => true])->assertServiceUnavailable();

    $this->assertDatabaseCount('rewarded_task_sessions', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('requires an enabled and fully configured provider before starting tasks', function (string $key, mixed $value): void {
    config()->set('rewarded_tasks.'.$key, $value);
    $product = ShopProduct::factory()->create();
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->getJson(rewardedTaskUrl($product))->assertOk()->assertJsonPath('enabled', false);
    $this->postJson(rewardedTaskUrl($product))->assertServiceUnavailable();

    $this->assertDatabaseCount('rewarded_task_sessions', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
})->with([
    'disabled' => ['adgem.enabled', false],
    'reward mapping not agreed' => ['adgem.reward_mapping_confirmed', false],
    'missing app' => ['adgem.app_id', ''],
    'missing secret' => ['adgem.postback_key', ''],
    'invalid reward' => ['reward_amount', 0],
]);

it('requires authentication for starting and reading task sessions', function (): void {
    $product = ShopProduct::factory()->create();

    $this->getJson(rewardedTaskUrl($product))->assertOk()->assertJsonPath('enabled', true);
    $this->postJson(rewardedTaskUrl($product))->assertUnauthorized();
    $this->getJson(rewardedTaskUrl($product).'/'.Str::uuid())->assertUnauthorized();

    $this->assertDatabaseCount('rewarded_task_sessions', 0);
});

it('starts optional AdGem sessions with a stable player and an independent product binding', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create();
    $otherProduct = ShopProduct::factory()->create();
    Sanctum::actingAs($buyer);

    $response = $this->postJson(rewardedTaskUrl($product))->assertCreated()
        ->assertJsonPath('task.status', 'pending')->assertJsonPath('task.reward_amount', 1000)
        ->assertJsonCount(0, 'task.discount_codes')->assertDontSee('server-only-test-secret');
    $first = RewardedTaskSession::query()->findOrFail($response->json('task.id'));
    $second = startRewardedTask($buyer, $otherProduct);
    $url = parse_url($response->json('task.offerwall_url'));
    parse_str($url['query'], $query);

    expect($url['scheme'])->toBe('https');
    expect($url['host'])->toBe('api.adgem.com');
    expect($query)->toBe(['appid' => '12345', 'playerid' => $first->player_id, 'c1' => $first->getKey(), 'c2' => $first->binding_token]);
    expect($second->player_id)->toBe($first->player_id);
    expect($second->binding_token)->not->toBe($first->binding_token);
    expect($first->expires_at->toDateTimeString())->toBe(now()->addDays(90)->toDateTimeString());
    $this->assertDatabaseHas('rewarded_task_sessions', ['id' => $first->getKey(), 'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey()]);
    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('rejects client supplied provider reward and verification claims at task creation', function (): void {
    $product = ShopProduct::factory()->create();
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->postJson(rewardedTaskUrl($product), [
        'reward_amount' => 999999, 'reward_type' => 'fixed', 'reward_value' => 999999,
        'provider' => 'adsterra', 'verified' => true, 'completed_at' => now()->toISOString(),
    ])->assertUnprocessable()->assertJsonValidationErrors(['reward_amount', 'reward_type', 'reward_value', 'provider', 'verified', 'completed_at']);

    $this->assertDatabaseCount('rewarded_task_sessions', 0);
    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('issues one saved fixed 1000 dong coupon from a signed payable goal and acknowledges retries', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 20000]);
    $session = startRewardedTask($buyer, $product);
    config()->set('rewarded_tasks.reward_amount', 50000);
    $payload = rewardedPostback($session);

    sendRewardedPostback($payload)->assertOk()->assertJsonPath('status', 'rewarded');
    sendRewardedPostback($payload)->assertOk()->assertJsonPath('status', 'duplicate');
    $payload['request_id'] = (string) Str::uuid();
    sendRewardedPostback($payload)->assertOk()->assertJsonPath('status', 'duplicate');
    $this->getJson(rewardedTaskUrl($product).'/'.$session->getKey())->assertOk()
        ->assertJsonPath('task.status', 'rewarded')->assertJsonCount(1, 'task.discount_codes')
        ->assertJsonPath('task.discount_codes.0.fixed_amount', 1000)->assertJsonPath('task.discount_codes.0.is_available', true);

    $this->assertDatabaseCount('product_discount_codes', 1);
    $this->assertDatabaseCount('rewarded_task_conversions', 1);
    $this->assertDatabaseCount('product_ad_views', 0);
    $this->assertDatabaseHas('product_discount_codes', [
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'product_ad_view_id' => null, 'reward_type' => 'fixed', 'reward_value' => 1000,
    ]);
    $code = ProductDiscountCode::query()->sole();
    expect($code->saved_at->toDateTimeString())->toBe(now()->toDateTimeString());
    expect($code->expires_at->toDateTimeString())->toBe(now()->addDays(30)->toDateTimeString());
    expect(RewardedTaskConversion::query()->sole()->payout)->toBe('0.50000000');
});

it('issues separate coupons for distinct payable goals within an offerwall session', function (): void {
    $session = startRewardedTask(User::factory()->customer()->create(), ShopProduct::factory()->create());
    $first = rewardedPostback($session, ['goal_id' => 'goal-install-paid']);
    $second = rewardedPostback($session, ['goal_id' => 'goal-level-20']);

    sendRewardedPostback($first)->assertOk()->assertJsonPath('status', 'rewarded');
    sendRewardedPostback($second)->assertOk()->assertJsonPath('status', 'rewarded');
    sendRewardedPostback($second)->assertOk()->assertJsonPath('status', 'duplicate');

    $this->assertDatabaseCount('product_discount_codes', 2);
    $this->assertDatabaseCount('rewarded_task_conversions', 2);
    expect(ProductDiscountCode::query()->sum('reward_value'))->toBe(2000);
});

it('never treats elapsed time opening offers or a client completion endpoint as a confirmed reward', function (): void {
    $this->freezeTime();
    $product = ShopProduct::factory()->create();
    $session = startRewardedTask(User::factory()->customer()->create(), $product);
    $this->travel(1)->hours();

    $this->getJson(rewardedTaskUrl($product).'/'.$session->getKey())->assertOk()
        ->assertJsonPath('task.status', 'pending')->assertJsonCount(0, 'task.discount_codes');
    $this->postJson(rewardedTaskUrl($product).'/'.$session->getKey().'/complete', ['completed' => true])->assertNotFound();

    $this->assertDatabaseCount('product_discount_codes', 0);
    $this->assertDatabaseCount('rewarded_task_conversions', 0);
});

it('rejects unsigned or tampered postbacks without minting a coupon', function (string $signature): void {
    $session = startRewardedTask(User::factory()->customer()->create(), ShopProduct::factory()->create());

    sendRewardedPostback(rewardedPostback($session), $signature)->assertUnauthorized();

    $this->assertDatabaseCount('product_discount_codes', 0);
    $this->assertDatabaseCount('rewarded_task_conversions', 0);
})->with(['missing' => '', 'forged' => str_repeat('0', 64), 'malformed' => 'sha256=1234']);

it('verifies the original JSON bytes rather than a reserialized payload', function (): void {
    $session = startRewardedTask(User::factory()->customer()->create(), ShopProduct::factory()->create());
    $payload = rewardedPostback($session);
    $signature = hash_hmac('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), 'server-only-test-secret');

    sendRewardedPostback($payload, $signature)->assertUnauthorized();

    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('rejects signed malformed JSON and signed missing conversion fields', function (): void {
    $body = '{invalid';
    $this->call('POST', '/api/postbacks/adgem/v3', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_SIGNATURE' => hash_hmac('sha256', $body, 'server-only-test-secret'),
    ], $body)->assertBadRequest();
    sendRewardedPostback(['request_id' => 'request-1', 'timestamp' => now()->timestamp, 'data' => ['app_id' => '12345']])
        ->assertUnprocessable()->assertJsonValidationErrors(['data.player_id', 'data.c1', 'data.c2', 'data.conversion_id']);

    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('rejects signed callbacks for a different app player or session binding', function (array $overrides): void {
    $session = startRewardedTask(User::factory()->customer()->create(), ShopProduct::factory()->create());

    sendRewardedPostback(rewardedPostback($session, $overrides))->assertForbidden();

    $this->assertDatabaseCount('product_discount_codes', 0);
    $this->assertDatabaseCount('rewarded_task_conversions', 0);
})->with([
    'other app' => [['app_id' => '999']],
    'other player' => [['player_id' => 'another-player']],
    'other token' => [['c2' => str_repeat('a', 64)]],
]);

it('returns 404 when another user product or shop requests a task session', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create();
    $otherProduct = ShopProduct::factory()->create(['shop_owner_id' => $product->shop_owner_id]);
    $session = startRewardedTask($buyer, $product);
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->getJson(rewardedTaskUrl($product).'/'.$session->getKey())->assertNotFound();
    Sanctum::actingAs($buyer);
    $this->getJson(rewardedTaskUrl($otherProduct).'/'.$session->getKey())->assertNotFound();
    $foreignShop = User::factory()->shopOwner()->create();
    $this->getJson('/api/shops/'.$foreignShop->user_code.'/products/'.$product->slug.'/rewarded-tasks')->assertNotFound();

    $this->assertDatabaseCount('product_discount_codes', 0);
});

it('scopes identical product slugs to their own shop for task configuration creation and status', function (): void {
    $buyer = User::factory()->customer()->create();
    $firstProduct = ShopProduct::factory()->create(['name' => 'Sản phẩm chung']);
    $secondProduct = ShopProduct::factory()->create(['name' => 'Sản phẩm chung']);
    expect($firstProduct->slug)->toBe($secondProduct->slug);

    $this->getJson(rewardedTaskUrl($firstProduct))->assertOk()->assertJsonPath('enabled', true);
    $this->getJson(rewardedTaskUrl($secondProduct))->assertOk()->assertJsonPath('enabled', true);
    $firstTask = startRewardedTask($buyer, $firstProduct);
    $secondTask = startRewardedTask($buyer, $secondProduct);
    $this->getJson(rewardedTaskUrl($firstProduct).'/'.$firstTask->getKey())->assertOk();
    $this->getJson(rewardedTaskUrl($secondProduct).'/'.$secondTask->getKey())->assertOk();
    $this->getJson(rewardedTaskUrl($secondProduct).'/'.$firstTask->getKey())->assertNotFound();

    $this->assertDatabaseHas('rewarded_task_sessions', ['id' => $firstTask->getKey(), 'shop_product_id' => $firstProduct->getKey()]);
    $this->assertDatabaseHas('rewarded_task_sessions', ['id' => $secondTask->getKey(), 'shop_product_id' => $secondProduct->getKey()]);
});

it('acknowledges install or nonpayable events without granting rewards', function (array $overrides): void {
    $session = startRewardedTask(User::factory()->customer()->create(), ShopProduct::factory()->create());

    sendRewardedPostback(rewardedPostback($session, $overrides))->assertOk()->assertJsonPath('status', 'ignored');

    $this->assertDatabaseCount('product_discount_codes', 0);
    $this->assertDatabaseCount('rewarded_task_conversions', 0);
})->with([
    'install' => [['conversion_type' => 'install', 'amount' => 0]],
    'zero payout' => [['payout' => 0]],
    'negative payout' => [['payout' => -1]],
    'payout below ledger precision' => [['payout' => '1e-99']],
    'zero reward' => [['amount' => 0]],
]);

it('prevents a conversion replay under another session and request id reuse for another conversion', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create();
    $firstSession = startRewardedTask($buyer, $product);
    $secondSession = startRewardedTask($buyer, $product);
    $first = rewardedPostback($firstSession);
    sendRewardedPostback($first)->assertOk();
    $otherConversion = rewardedPostback($firstSession, ['conversion_id' => 'other-conversion']);
    $otherConversion['request_id'] = $first['request_id'];

    sendRewardedPostback($otherConversion)->assertConflict();
    sendRewardedPostback(rewardedPostback($secondSession))->assertConflict();

    $this->assertDatabaseCount('product_discount_codes', 1);
    $this->assertDatabaseCount('rewarded_task_conversions', 1);
});

it('accepts a delayed signed paid goal after the offerwall link expires', function (): void {
    $this->freezeTime();
    $product = ShopProduct::factory()->create();
    $session = startRewardedTask(User::factory()->customer()->create(), $product);
    $this->travel(91)->days();

    $this->getJson(rewardedTaskUrl($product).'/'.$session->getKey())->assertOk()->assertJsonPath('task.status', 'expired');
    sendRewardedPostback(rewardedPostback($session))->assertOk()->assertJsonPath('status', 'rewarded');
    $this->getJson(rewardedTaskUrl($product).'/'.$session->getKey())->assertOk()
        ->assertJsonPath('task.status', 'expired')->assertJsonCount(1, 'task.discount_codes');

    expect(ProductDiscountCode::query()->sole()->expires_at->toDateTimeString())->toBe(now()->addDays(30)->toDateTimeString());
});

it('redeems a confirmed 1000 dong coupon once for its buyer and product at checkout', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 20000, 'old_price' => null, 'stock' => 10]);
    $session = startRewardedTask($buyer, $product);
    sendRewardedPostback(rewardedPostback($session))->assertOk();
    $code = ProductDiscountCode::query()->sole();
    $payload = [
        'customer_name' => 'Người nhận thử nghiệm', 'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        'quantity' => 2, 'discount_codes' => [$code->code],
    ];

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(str_replace('/rewarded-tasks', '/orders', rewardedTaskUrl($product)), $payload)
        ->assertCreated()->assertJsonPath('order.discount_amount', 1000)->assertJsonPath('order.total', 39000);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(str_replace('/rewarded-tasks', '/orders', rewardedTaskUrl($product)), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 1);
    expect($code->fresh()->used_at)->not->toBeNull();
    expect($product->fresh()->stock)->toBe(8);
});

it('rejects a saved code with no historical view or verified provider conversion at checkout', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 20000, 'stock' => 10]);
    $code = ProductDiscountCode::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'code' => str_repeat('A', 32), 'original_price' => 20000, 'sale_price' => 19000,
        'reward_type' => 'fixed', 'reward_value' => 1000, 'saved_at' => now(), 'expires_at' => now()->addDays(30),
    ]);
    Sanctum::actingAs($buyer);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(str_replace('/rewarded-tasks', '/orders', rewardedTaskUrl($product)), [
            'customer_name' => 'Người nhận thử nghiệm', 'customer_phone' => '0901234567',
            'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
            'quantity' => 1, 'discount_codes' => [$code->code],
        ])->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($code->fresh()->used_at)->toBeNull();
});
