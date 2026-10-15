<?php

use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\ProductDiscountCodeService;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function stackedRewardCode(User $buyer, ShopProduct $product, string $type, int $value, bool $saved = true): ProductDiscountCode
{
    $view = ProductAdView::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'provider' => 'verified_fixture', 'campaign_id' => 'fixed-credit', 'ad_type' => 'rewarded',
        'reward_type' => $type, 'reward_value' => $value,
        'video_url' => '', 'duration_seconds' => 0,
        'completed_at' => now(), 'verified_at' => now(),
        'verification_reference' => (string) Str::uuid(), 'expires_at' => now()->addHour(),
    ]);
    $service = app(ProductDiscountCodeService::class);
    $code = $service->issueFromCompletedView($buyer, $view);

    return $saved ? $service->save($buyer, $code) : $code;
}

function stackedOrderPayload(array $attributes = []): array
{
    return [
        'customer_name' => 'Người nhận ưu đãi', 'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        'quantity' => 2,
        ...$attributes,
    ];
}

function stackedOrderUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders';
}

it('stacks fixed credits once per order to zero and replays without consuming rewards or stock twice', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $codes = collect([70000, 80000, 50000])->map(fn (int $amount): ProductDiscountCode => stackedRewardCode($buyer, $product, 'fixed', $amount));
    $payload = stackedOrderPayload(['discount_codes' => $codes->pluck('code')->all()]);
    $key = (string) Str::uuid();
    Sanctum::actingAs($buyer);

    $response = $this->withHeader('Idempotency-Key', $key)->postJson(stackedOrderUrl($product), $payload)
        ->assertCreated()->assertJsonPath('order.subtotal', 200000)
        ->assertJsonPath('order.discount_amount', 200000)->assertJsonPath('order.total', 0);

    $orderId = $response->json('order.id');
    expect($response->json('order.redeemed_discount_codes'))->toHaveCount(3)->toContain(...$codes->pluck('code')->all());
    foreach ($codes as $code) {
        $this->assertDatabaseHas('product_discount_codes', ['id' => $code->id, 'shop_order_id' => $orderId]);
        $this->assertDatabaseHas('product_ad_views', ['id' => $code->product_ad_view_id, 'shop_order_id' => $orderId]);
        expect($code->fresh()->used_at)->not->toBeNull();
    }
    $consumedAt = $codes->map(fn (ProductDiscountCode $code): string => $code->fresh()->used_at->toISOString())->all();
    $this->assertDatabaseHas('shop_orders', ['id' => $orderId, 'subtotal' => 200000, 'discount_amount' => 200000, 'total' => 0]);
    $this->assertDatabaseHas('shop_order_items', ['shop_order_id' => $orderId, 'unit_price' => 100000, 'quantity' => 2, 'subtotal' => 200000]);
    $this->travel(31)->days();

    $this->withHeader('Idempotency-Key', $key)->postJson(stackedOrderUrl($product), $payload)
        ->assertCreated()->assertJsonPath('order.id', $orderId)->assertJsonPath('order.total', 0)
        ->assertJsonCount(3, 'order.redeemed_discount_codes');

    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
    expect($product->fresh()->stock)->toBe(6);
    expect($codes->map(fn (ProductDiscountCode $code): string => $code->fresh()->used_at->toISOString())->all())->toBe($consumedAt);
});

it('clips the last contributing code and preserves later credits in the buyers selection order', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $unused = stackedRewardCode($buyer, $product, 'fixed', 5000);
    $clipped = stackedRewardCode($buyer, $product, 'fixed', 160000);
    $percentage = stackedRewardCode($buyer, $product, 'percentage', 2500);
    Sanctum::actingAs($buyer);

    $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'discount_codes' => [$percentage->code, $clipped->code, $unused->code],
    ]))->assertCreated()->assertJsonPath('order.subtotal', 200000)
        ->assertJsonPath('order.discount_amount', 200000)->assertJsonPath('order.total', 0);

    $orderId = $response->json('order.id');
    expect($response->json('order.redeemed_discount_codes'))->toHaveCount(2)->toContain($percentage->code, $clipped->code)->not->toContain($unused->code);
    foreach ([$percentage, $clipped] as $code) {
        $this->assertDatabaseHas('product_discount_codes', ['id' => $code->id, 'shop_order_id' => $orderId]);
        $this->assertDatabaseHas('product_ad_views', ['id' => $code->product_ad_view_id, 'shop_order_id' => $orderId]);
        expect($code->fresh()->used_at)->not->toBeNull();
    }
    $this->assertDatabaseHas('product_discount_codes', ['id' => $unused->id, 'used_at' => null, 'shop_order_id' => null]);
    $this->assertDatabaseHas('product_ad_views', ['id' => $unused->product_ad_view_id, 'shop_order_id' => null]);
    expect(app(ProductDiscountCodeService::class)->payload($unused->fresh())['is_available'])->toBeTrue();

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'discount_codes' => [$clipped->code],
    ]))->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
    expect($product->fresh()->stock)->toBe(6);
});

it('calculates every percentage from the original subtotal with half up rounding before adding fixed credit', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 1001, 'old_price' => null, 'stock' => 10]);
    $tenPercent = stackedRewardCode($buyer, $product, 'percentage', 1000);
    $twentyPercent = stackedRewardCode($buyer, $product, 'percentage', 2000);
    $fixed = stackedRewardCode($buyer, $product, 'fixed', 1000);
    Sanctum::actingAs($buyer);

    $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'quantity' => 5, 'discount_codes' => [$tenPercent->code, $twentyPercent->code, $fixed->code],
    ]))->assertCreated()->assertJsonPath('order.subtotal', 5005)
        ->assertJsonPath('order.discount_amount', 2502)->assertJsonPath('order.total', 2503)
        ->assertJsonCount(3, 'order.redeemed_discount_codes');

    $this->assertDatabaseHas('shop_orders', ['id' => $response->json('order.id'), 'subtotal' => 5005, 'discount_amount' => 2502, 'total' => 2503]);
    expect($product->fresh()->stock)->toBe(5);
});

it('keeps legacy per unit discount ratios when stacking them with new campaign credits', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 2000, 'old_price' => 3000, 'stock' => 8]);
    $legacy = stackedRewardCode($buyer, $product, 'fixed', 1);
    $legacy->update(['reward_type' => null, 'reward_value' => null, 'original_price' => 2, 'sale_price' => 1]);
    $percentage = stackedRewardCode($buyer, $product, 'percentage', 1000);
    $fixed = stackedRewardCode($buyer, $product, 'fixed', 1000);
    Sanctum::actingAs($buyer);

    $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'quantity' => 3, 'discount_codes' => [$legacy->code, $percentage->code, $fixed->code],
    ]))->assertCreated()->assertJsonPath('order.subtotal', 9000)
        ->assertJsonPath('order.discount_amount', 6400)->assertJsonPath('order.total', 2600)
        ->assertJsonCount(3, 'order.redeemed_discount_codes');

    $this->assertDatabaseHas('shop_orders', ['id' => $response->json('order.id'), 'subtotal' => 9000, 'discount_amount' => 6400, 'total' => 2600]);
    expect($product->fresh()->stock)->toBe(5);
});

it('accepts the single code request format for new fixed campaign credits', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $code = stackedRewardCode($buyer, $product, 'fixed', 25000);
    Sanctum::actingAs($buyer);

    $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'discount_code' => $code->code,
    ]))->assertCreated()->assertJsonPath('order.discount_amount', 25000)->assertJsonPath('order.total', 175000)
        ->assertJsonPath('order.redeemed_discount_codes', [$code->code]);

    $this->assertDatabaseHas('product_discount_codes', ['id' => $code->id, 'shop_order_id' => $response->json('order.id')]);
    expect($product->fresh()->stock)->toBe(6);
});

it('returns 422 atomically when any selected credit is invalid even after earlier credits cover the entire order', function (string $failure): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 50000, 'old_price' => null, 'stock' => 8]);
    $first = stackedRewardCode($buyer, $product, 'fixed', 100000);
    $last = stackedRewardCode($buyer, $product, 'fixed', 5000);
    $invalid = match ($failure) {
        'wrong owner' => stackedRewardCode(User::factory()->customer()->create(), $product, 'fixed', 5000),
        'wrong product' => stackedRewardCode($buyer, ShopProduct::factory()->for($product->shopOwner, 'shopOwner')->create(), 'fixed', 5000),
        'not saved' => stackedRewardCode($buyer, $product, 'fixed', 5000, false),
        'unknown' => null,
        default => stackedRewardCode($buyer, $product, 'fixed', 5000),
    };
    match ($failure) {
        'expired' => $invalid->update(['expires_at' => now()->subSecond()]),
        'used' => $invalid->update(['used_at' => now()]),
        default => null,
    };
    $before = ProductDiscountCode::query()->orderBy('id')->get()->toArray();
    Sanctum::actingAs($buyer);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'discount_codes' => [$first->code, $invalid?->code ?? str_repeat('Z', 32), $last->code],
    ]))->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(8);
    expect(ProductDiscountCode::query()->orderBy('id')->get()->toArray())->toBe($before);
    foreach ([$first, $last] as $code) {
        $this->assertDatabaseHas('product_ad_views', ['id' => $code->product_ad_view_id, 'shop_order_id' => null]);
    }
})->with(['wrong owner', 'wrong product', 'expired', 'used', 'not saved', 'unknown']);

it('returns 422 without reserving stock for malformed lists duplicate credits or mixed reward formats', function (string $failure, array $errors): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $code = stackedRewardCode($buyer, $product, 'fixed', 25000);
    $attributes = match ($failure) {
        'duplicate' => ['discount_codes' => [$code->code, $code->code]],
        'normalized duplicate' => ['discount_codes' => [$code->code, ' '.strtolower($code->code).' ']],
        'single and list' => ['discount_codes' => [$code->code], 'discount_code' => $code->code],
        'view and list' => ['discount_codes' => [$code->code], 'advertisement_view_id' => $code->product_ad_view_id],
        'too many' => ['discount_codes' => array_map(fn (int $index): string => str_pad((string) $index, 32, 'X'), range(1, 101))],
        'not a list' => ['discount_codes' => ['first' => $code->code]],
        'not an array' => ['discount_codes' => $code->code],
        'short code' => ['discount_codes' => [substr($code->code, 1)]],
        'non string code' => ['discount_codes' => [123]],
    };
    Sanctum::actingAs($buyer);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(stackedOrderUrl($product), stackedOrderPayload($attributes))
        ->assertUnprocessable()->assertJsonValidationErrors($errors);

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    $this->assertDatabaseHas('product_discount_codes', ['id' => $code->id, 'used_at' => null, 'shop_order_id' => null]);
    $this->assertDatabaseHas('product_ad_views', ['id' => $code->product_ad_view_id, 'shop_order_id' => null]);
    expect($product->fresh()->stock)->toBe(8);
})->with([
    'duplicate' => ['duplicate', ['discount_codes.0', 'discount_codes.1']],
    'normalized duplicate' => ['normalized duplicate', ['discount_codes.0', 'discount_codes.1']],
    'single and list' => ['single and list', ['discount_code', 'discount_codes']],
    'view and list' => ['view and list', ['discount_codes']],
    'too many' => ['too many', ['discount_codes']],
    'not a list' => ['not a list', ['discount_codes']],
    'not an array' => ['not an array', ['discount_codes']],
    'short code' => ['short code', ['discount_codes.0']],
    'non string code' => ['non string code', ['discount_codes.0']],
]);

it('returns 409 when a checkout key is replayed with a different credit selection order', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $first = stackedRewardCode($buyer, $product, 'fixed', 25000);
    $second = stackedRewardCode($buyer, $product, 'fixed', 50000);
    $key = (string) Str::uuid();
    Sanctum::actingAs($buyer);
    $response = $this->withHeader('Idempotency-Key', $key)->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'discount_codes' => [$first->code, $second->code],
    ]))->assertCreated()->assertJsonPath('order.total', 125000);

    $this->withHeader('Idempotency-Key', $key)->postJson(stackedOrderUrl($product), stackedOrderPayload([
        'discount_codes' => [$second->code, $first->code],
    ]))->assertConflict();

    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
    foreach ([$first, $second] as $code) {
        $this->assertDatabaseHas('product_discount_codes', ['id' => $code->id, 'shop_order_id' => $response->json('order.id')]);
    }
    expect($product->fresh()->stock)->toBe(6);
});

it('does not turn an unknown reward type into a legacy percentage discount', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $code = stackedRewardCode($buyer, $product, 'fixed', 25000);
    $code->update(['reward_type' => 'unsupported', 'original_price' => 100000, 'sale_price' => 1]);
    Sanctum::actingAs($buyer);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(stackedOrderUrl($product), stackedOrderPayload(['discount_codes' => [$code->code]]))
        ->assertCreated()->assertJsonPath('order.total', 200000)
        ->assertJsonPath('order.discount_amount', 0)->assertJsonCount(0, 'order.redeemed_discount_codes');

    expect($code->fresh()->used_at)->toBeNull();
});
