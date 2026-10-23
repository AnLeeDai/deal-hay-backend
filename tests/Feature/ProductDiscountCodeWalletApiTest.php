<?php

use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function walletProduct(array $attributes = []): ShopProduct
{
    return ShopProduct::factory()->create([
        'price' => 80_000,
        'old_price' => 100_000,
        'stock' => 10,
        'advertisement_video_url' => 'https://example.com/ad.mp4',
        'advertisement_duration_seconds' => 15,
        ...$attributes,
    ]);
}

function walletProductUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug;
}

function walletCode(User $buyer, ShopProduct $product): ProductDiscountCode
{
    $view = ProductAdView::query()->create([
        'user_id' => $buyer->getKey(),
        'shop_product_id' => $product->getKey(),
        'video_url' => $product->advertisement_video_url,
        'duration_seconds' => $product->advertisement_duration_seconds,
        'completed_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    return ProductDiscountCode::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'product_ad_view_id' => $view->getKey(), 'code' => Str::upper(Str::random(32)),
        'original_price' => $product->old_price, 'sale_price' => $product->price,
        'expires_at' => now()->addDays(30),
    ]);
}

function walletOrderPayload(array $attributes = []): array
{
    return [
        'customer_name' => 'Người nhận thử nghiệm',
        'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài',
        'shipping_province_code' => 1,
        'shipping_ward_code' => 70,
        'quantity' => 2,
        ...$attributes,
    ];
}

it('never issues a new code from old seller video sessions even after elapsed time or client completion claims', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $otherBuyer = User::factory()->customer()->create();
    $product = walletProduct();
    $base = walletProductUrl($product);
    Sanctum::actingAs($buyer);
    $viewId = ProductAdView::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'video_url' => 'https://example.com/old-shop-video.mp4', 'duration_seconds' => 15,
        'expires_at' => now()->addHour(),
    ])->getKey();

    $this->postJson($base.'/advertisement/'.$viewId.'/complete')->assertNotFound();
    $this->assertDatabaseCount('product_discount_codes', 0);
    Sanctum::actingAs($otherBuyer);
    $this->postJson($base.'/advertisement/'.$viewId.'/complete')->assertNotFound();
    $this->assertDatabaseCount('product_discount_codes', 0);
    $this->travel(15)->seconds();

    Sanctum::actingAs($buyer);
    $this->postJson($base.'/advertisement/'.$viewId.'/complete', ['verified_at' => now()->toISOString(), 'reward_value' => 4294967295])
        ->assertNotFound();

    $this->assertDatabaseCount('product_discount_codes', 0);
    expect(ProductAdView::query()->findOrFail($viewId)->completed_at)->toBeNull();
});

it('lists only the owners codes supports product filtering and serializes storage image URLs', function (): void {
    $buyer = User::factory()->customer()->create();
    $otherBuyer = User::factory()->customer()->create();
    $product = walletProduct(['image_url' => 'products/wallet-image.webp']);
    $otherProduct = walletProduct();
    $code = walletCode($buyer, $product);
    walletCode($buyer, $otherProduct);
    $privateCode = walletCode($otherBuyer, $product);

    $this->withToken($buyer->createToken('wallet-list')->plainTextToken)->getJson('/api/discount-codes')
        ->assertOk()->assertJsonCount(2, 'discount_codes')->assertDontSee($privateCode->code);
    $this->getJson('/api/discount-codes?product_id='.$product->getKey())
        ->assertOk()->assertJsonCount(1, 'discount_codes')
        ->assertJsonPath('discount_codes.0.id', $code->getKey())
        ->assertJsonPath('discount_codes.0.product.image_url', route('media.asset', ['path' => 'products/wallet-image.webp'], absolute: false));
});

it('requires saving a code before checkout and allows using it after the source view expires', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $code = walletCode($buyer, $product);
    $payload = walletOrderPayload(['discount_code' => $code->code]);
    $this->withToken($buyer->createToken('wallet-save-use')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');
    $this->assertDatabaseCount('shop_orders', 0);

    $saved = $this->postJson('/api/discount-codes/'.$code->getKey().'/save')->assertOk()
        ->assertJsonPath('discount_code.is_available', true)->json('discount_code.saved_at');
    $this->travel(3)->days();
    $this->postJson('/api/discount-codes/'.$code->getKey().'/save')->assertOk()
        ->assertJsonPath('discount_code.saved_at', $saved);
    $key = (string) Str::uuid();
    $first = $this->withHeader('Idempotency-Key', $key)->postJson(walletProductUrl($product).'/orders', $payload)
        ->assertCreated()->assertJsonPath('order.discount_amount', 40_000)->assertJsonPath('order.total', 160_000);
    $this->postJson(walletProductUrl($product).'/orders', $payload)->assertCreated()
        ->assertJsonPath('order.id', $first->json('order.id'));

    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
    expect($product->fresh()->stock)->toBe(8)
        ->and($code->fresh()->used_at)->not->toBeNull();
    $this->postJson('/api/discount-codes/'.$code->getKey().'/save')->assertUnprocessable();
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', $payload)->assertUnprocessable();
    $this->assertDatabaseCount('shop_orders', 1);
});

it('returns 404 on saving another users code and 422 on wrong user or product redemption', function (): void {
    $buyer = User::factory()->customer()->create();
    $otherBuyer = User::factory()->customer()->create();
    $product = walletProduct();
    $otherProduct = walletProduct(['shop_owner_id' => $product->shop_owner_id]);
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()]);

    Sanctum::actingAs($otherBuyer);
    $this->postJson('/api/discount-codes/'.$code->getKey().'/save')->assertNotFound();
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['discount_code' => $code->code]))
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');
    Sanctum::actingAs($buyer);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($otherProduct).'/orders', walletOrderPayload(['discount_code' => $code->code]))
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($product->fresh()->stock)->toBe(10)->and($code->fresh()->used_at)->toBeNull();
});

it('returns 422 for an expired saved code without creating an order or reserving stock', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()]);
    $this->travel(31)->days();

    $this->withToken($buyer->createToken('wallet-expired')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['discount_code' => $code->code]))
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');
    $this->postJson('/api/discount-codes/'.$code->getKey().'/save')->assertUnprocessable();
    $this->getJson('/api/discount-codes')->assertOk()->assertJsonPath('discount_codes.0.is_available', false);

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(10)->and($code->fresh()->used_at)->toBeNull();
});

it('honors the issued discount ratio for variants after later product price changes', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['price' => 175_000, 'stock' => 5]);
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()]);
    $product->update(['price' => 100_000, 'old_price' => 200_000]);

    $this->withToken($buyer->createToken('wallet-snapshot-ratio')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['discount_code' => $code->code, 'variant_id' => $variant->getKey()]))
        ->assertCreated()->assertJsonPath('order.subtotal', 700_000)
        ->assertJsonPath('order.discount_amount', 140_000)->assertJsonPath('order.total', 560_000);

    $this->assertDatabaseHas('shop_orders', ['total' => 560_000, 'discount_amount' => 140_000]);
    expect($variant->fresh()->stock)->toBe(3);
});

it('rounds a saved ratio up at exactly half a dong using the same integer terms as checkout', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = walletProduct(['price' => 1_000, 'old_price' => 2_000]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['price' => 1_000, 'stock' => 5]);
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()]);
    $product->update(['price' => 2_000, 'old_price' => 3_001]);

    $this->withToken($buyer->createToken('wallet-exact-half-rounding')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['discount_code' => $code->code, 'variant_id' => $variant->getKey()]))
        ->assertCreated()->assertJsonPath('order.subtotal', 3_002)
        ->assertJsonPath('order.discount_amount', 1_500)->assertJsonPath('order.total', 1_502);

    $this->assertDatabaseHas('shop_orders', ['subtotal' => 3_002, 'total' => 1_502]);
});

it('returns 404 without issuing a code when completing a view under the wrong product', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $otherProduct = walletProduct(['shop_owner_id' => $product->shop_owner_id]);
    $this->withToken($buyer->createToken('wallet-wrong-product-complete')->plainTextToken);
    $viewId = ProductAdView::query()->create([
        'user_id' => $buyer->getKey(), 'shop_product_id' => $product->getKey(),
        'video_url' => 'https://example.com/old-shop-video.mp4', 'duration_seconds' => 15,
        'expires_at' => now()->addHour(),
    ])->getKey();
    $this->travel(15)->seconds();

    $this->postJson(walletProductUrl($otherProduct).'/advertisement/'.$viewId.'/complete')->assertNotFound();

    $this->assertDatabaseCount('product_discount_codes', 0);
    expect(ProductAdView::query()->findOrFail($viewId)->completed_at)->toBeNull();
});

it('requires authentication to read or save discount codes', function (): void {
    $buyer = User::factory()->customer()->create();
    $code = walletCode($buyer, walletProduct());

    $this->getJson('/api/discount-codes')->assertUnauthorized();
    $this->postJson('/api/discount-codes/'.$code->getKey().'/save')->assertUnauthorized();

    expect($code->fresh()->saved_at)->toBeNull();
});

it('rejects raw legacy views while preserving redemption of already issued saved codes', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()]);
    $codePayload = walletOrderPayload(['discount_code' => $code->code]);
    $viewPayload = walletOrderPayload(['advertisement_view_id' => $code->product_ad_view_id]);
    $this->withToken($buyer->createToken('wallet-dual-path')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', $viewPayload)->assertUnprocessable();
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', $codePayload)->assertCreated()->assertJsonPath('order.total', 160_000);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', $viewPayload)->assertUnprocessable();

    $this->assertDatabaseCount('shop_orders', 1);
    expect($product->fresh()->stock)->toBe(8)->and($code->fresh()->used_at)->not->toBeNull();
});

it('does not restore a used code or legacy view when its order is deleted', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()]);
    $this->withToken($buyer->createToken('wallet-used-order-deleted')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['discount_code' => $code->code]))->assertCreated();
    ShopOrder::query()->firstOrFail()->delete();

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['advertisement_view_id' => $code->product_ad_view_id]))
        ->assertUnprocessable()->assertJsonValidationErrors('advertisement_view_id');
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['discount_code' => $code->code]))
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($code->fresh()->used_at)->not->toBeNull();
});

it('preserves idempotent replay of orders fingerprinted before discount codes existed', function (bool $withView): void {
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $viewId = $withView ? (string) Str::uuid() : null;
    $payload = walletOrderPayload($withView ? ['advertisement_view_id' => $viewId] : []);
    $key = (string) Str::uuid();
    $legacyFingerprint = hash('sha256', json_encode([
        $buyer->getKey(), $product->getKey(), null, 2,
        'Người nhận thử nghiệm', '0901234567', '12 Hàng Bài',
        1, 70, null, $viewId,
    ], JSON_THROW_ON_ERROR));
    $order = ShopOrder::factory()->create([
        'shop_owner_id' => $product->shop_owner_id, 'customer_id' => $buyer->getKey(),
        'idempotency_key' => $key, 'request_fingerprint' => $legacyFingerprint,
    ]);

    $this->withToken($buyer->createToken('wallet-legacy-replay')->plainTextToken)
        ->withHeader('Idempotency-Key', $key)
        ->postJson(walletProductUrl($product).'/orders', $payload)
        ->assertCreated()->assertJsonPath('order.id', $order->getKey());

    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(10);
})->with(['regular legacy order' => false, 'advertisement legacy order' => true]);

it('rejects combining discount codes and legacy views in one request', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()]);

    $this->withToken($buyer->createToken('wallet-duplicate-discount')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload([
            'discount_code' => $code->code, 'advertisement_view_id' => $code->product_ad_view_id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($code->fresh()->used_at)->toBeNull();
});

it('expires codes at the exact expiry instant for saving listing and checkout', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = walletProduct();
    $code = walletCode($buyer, $product);
    $code->update(['saved_at' => now()->subMinute(), 'expires_at' => now()]);
    $this->withToken($buyer->createToken('wallet-exact-expiry')->plainTextToken);

    $this->getJson('/api/discount-codes')->assertOk()
        ->assertJsonPath('discount_codes.0.is_available', false);
    $this->postJson('/api/discount-codes/'.$code->getKey().'/save')
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(walletProductUrl($product).'/orders', walletOrderPayload(['discount_code' => $code->code]))
        ->assertUnprocessable()->assertJsonValidationErrors('discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($product->fresh()->stock)->toBe(10)->and($code->fresh()->used_at)->toBeNull();
});
