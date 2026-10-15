<?php

use App\Models\ProductAdView;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Support\Str;

function customerOrderBoundaryPayload(array $overrides = []): array
{
    return [
        'quantity' => 5,
        'customer_name' => 'Người nhận thử nghiệm',
        'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài',
        'shipping_province_code' => 1,
        'shipping_ward_code' => 70,
        ...$overrides,
    ];
}

it('returns 422 before reserving stock when a normal order exceeds the monetary storage limit', function (): void {
    $customer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 999_999_999, 'stock' => 10]);

    $this->withToken($customer->createToken('order-subtotal-boundary')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson('/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders', customerOrderBoundaryPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_order_items', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(10);
});

it('returns 422 instead of overflowing when a variant markup and quantity produce an excessive total', function (): void {
    $customer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->hasVariants()->create([
        'price' => 1_000,
        'old_price' => 999_999_999,
        'advertisement_video_url' => 'https://example.com/advertisement.mp4',
        'advertisement_duration_seconds' => 5,
        'stock' => 20_000,
    ]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create([
        'price' => 999_999_999,
        'stock' => 20_000,
    ]);

    $this->withToken($customer->createToken('order-variant-boundary')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson('/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders', customerOrderBoundaryPayload([
            'variant_id' => $variant->getKey(),
            'quantity' => 9_999,
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($variant->fresh()->stock)->toBe(20_000);
});

it('returns 422 without consuming an advertisement when the original subtotal exceeds the limit', function (): void {
    $this->freezeTime();
    $customer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create([
        'price' => 600_000_000,
        'old_price' => 999_999_999,
        'advertisement_video_url' => 'https://example.com/advertisement.mp4',
        'advertisement_duration_seconds' => 5,
        'stock' => 10,
    ]);
    $view = ProductAdView::query()->create([
        'user_id' => $customer->getKey(),
        'shop_product_id' => $product->getKey(),
        'video_url' => $product->advertisement_video_url,
        'duration_seconds' => 5,
        'completed_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    $this->withToken($customer->createToken('order-discount-boundary')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson('/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders', customerOrderBoundaryPayload([
            'advertisement_view_id' => $view->getKey(),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(10)
        ->and($view->fresh()->shop_order_id)->toBeNull();
});

it('accepts an order whose subtotal is exactly the monetary storage limit', function (): void {
    $customer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 858_993_459, 'stock' => 5]);

    $this->withToken($customer->createToken('order-exact-boundary')->plainTextToken)
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson('/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders', customerOrderBoundaryPayload())
        ->assertCreated()
        ->assertJsonPath('order.subtotal', 4_294_967_295)
        ->assertJsonPath('order.discount_amount', 0)
        ->assertJsonPath('order.total', 4_294_967_295);

    $this->assertDatabaseHas('shop_orders', [
        'customer_id' => $customer->getKey(),
        'subtotal' => 4_294_967_295,
        'total' => 4_294_967_295,
    ]);
    $this->assertDatabaseHas('shop_order_items', [
        'shop_product_id' => $product->getKey(),
        'quantity' => 5,
        'subtotal' => 4_294_967_295,
    ]);
    expect($product->fresh()->stock)->toBe(0);
});
