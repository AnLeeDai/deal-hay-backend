<?php

use App\Models\ProductAdView;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Support\Str;

function advertisementSecurityProduct(array $attributes = []): ShopProduct
{
    return ShopProduct::factory()->create([
        'price' => 80_000,
        'old_price' => 100_000,
        'stock' => 8,
        'advertisement_video_url' => 'https://example.com/original-ad.mp4',
        'advertisement_duration_seconds' => 15,
        ...$attributes,
    ]);
}

function advertisementSecurityView(User $buyer, ShopProduct $product, array $attributes = []): ProductAdView
{
    $view = ProductAdView::query()->create([
        'user_id' => $buyer->getKey(),
        'shop_product_id' => $product->getKey(),
        'video_url' => $product->advertisement_video_url,
        'duration_seconds' => $product->advertisement_duration_seconds,
        'completed_at' => now(),
        'expires_at' => now()->addHour(),
        ...$attributes,
    ]);
    $view->created_at = now()->subMinute();
    $view->save();

    return $view;
}

function advertisementSecurityProductUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug;
}

function advertisementSecurityOrderPayload(array $attributes = []): array
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

it('returns 422 for an advertisement whose video or duration changed before redemption', function (array $changedAdvertisement): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = advertisementSecurityProduct();
    $view = advertisementSecurityView($buyer, $product);
    $product->update($changedAdvertisement);
    $base = advertisementSecurityProductUrl($product);

    $this->withToken($buyer->createToken('stale-advertisement')->plainTextToken)
        ->postJson($base.'/advertisement/'.$view->getKey().'/complete')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('advertisement_view_id');

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson($base.'/orders', advertisementSecurityOrderPayload(['advertisement_view_id' => $view->getKey()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('advertisement_view_id');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($product->fresh()->stock)->toBe(8)
        ->and($view->fresh()->shop_order_id)->toBeNull();
})->with([
    'changed video' => [['advertisement_video_url' => 'https://example.com/replacement-ad.mp4']],
    'changed duration' => [['advertisement_duration_seconds' => 30]],
]);

it('returns 422 when attempting to complete an expired advertisement', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = advertisementSecurityProduct();
    $view = advertisementSecurityView($buyer, $product, [
        'completed_at' => null,
        'expires_at' => now()->subSecond(),
    ]);

    $this->withToken($buyer->createToken('expired-advertisement-completion')->plainTextToken)
        ->postJson(advertisementSecurityProductUrl($product).'/advertisement/'.$view->getKey().'/complete')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('advertisement_view_id');

    expect($view->fresh()->completed_at)->toBeNull();
});

it('returns 404 for completion and 422 for checkout using another products advertisement', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = advertisementSecurityProduct();
    $otherProduct = advertisementSecurityProduct(['shop_owner_id' => $product->shop_owner_id]);
    $view = advertisementSecurityView($buyer, $otherProduct);
    $base = advertisementSecurityProductUrl($product);

    $this->withToken($buyer->createToken('wrong-product-advertisement')->plainTextToken)
        ->postJson($base.'/advertisement/'.$view->getKey().'/complete')
        ->assertNotFound();

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson($base.'/orders', advertisementSecurityOrderPayload(['advertisement_view_id' => $view->getKey()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('advertisement_view_id');

    $this->assertDatabaseCount('shop_orders', 0);
    expect($product->fresh()->stock)->toBe(8)
        ->and($view->fresh()->shop_order_id)->toBeNull();
});

it('replays a discounted order with the same key after consuming its advertisement exactly once', function (): void {
    $this->freezeTime();
    $buyer = User::factory()->customer()->create();
    $product = advertisementSecurityProduct();
    $view = advertisementSecurityView($buyer, $product);
    $payload = advertisementSecurityOrderPayload(['advertisement_view_id' => $view->getKey()]);
    $base = advertisementSecurityProductUrl($product);
    $key = (string) Str::uuid();

    $first = $this->withToken($buyer->createToken('advertisement-order-replay')->plainTextToken)
        ->withHeader('Idempotency-Key', $key)
        ->postJson($base.'/orders', $payload)
        ->assertCreated()
        ->assertJsonPath('order.total', 160_000);

    $this->postJson($base.'/orders', $payload)
        ->assertCreated()
        ->assertJsonPath('order.id', $first->json('order.id'))
        ->assertJsonPath('order.total', 160_000);

    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_order_items', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
    expect($product->fresh()->stock)->toBe(6)
        ->and($view->fresh()->shop_order_id)->toBe($first->json('order.id'));
});

it('returns 422 for variants from another product or variants no longer on sale', function (string $invalidVariant): void {
    $buyer = User::factory()->customer()->create();
    $product = advertisementSecurityProduct();
    $variantProduct = $invalidVariant === 'foreign product'
        ? advertisementSecurityProduct(['shop_owner_id' => $product->shop_owner_id])
        : $product;
    $variant = ShopProductVariant::factory()->for($variantProduct, 'product')->create([
        'stock' => 5,
        'is_active' => $invalidVariant !== 'inactive',
        'retired_at' => $invalidVariant === 'retired' ? '2026-01-01 00:00:00' : null,
    ]);

    $this->withToken($buyer->createToken('invalid-order-variant')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(advertisementSecurityProductUrl($product).'/orders', advertisementSecurityOrderPayload(['variant_id' => $variant->getKey()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('variant_id');

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($variant->fresh()->stock)->toBe(5);
})->with(['foreign product', 'inactive', 'retired']);
