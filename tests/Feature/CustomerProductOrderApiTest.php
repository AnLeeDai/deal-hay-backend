<?php

use App\Models\ProductAdView;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function customerOrderPayload(array $overrides = []): array
{
    return [...[
        'customer_name' => 'Nguyễn Văn A', 'customer_phone' => '0901234567',
        'shipping_address' => '12 Phố Huế', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        'quantity' => 2,
    ], ...$overrides];
}

function customerProductUrl(User $shop, ShopProduct $product): string
{
    return '/api/shops/'.$shop->user_code.'/products/'.$product->slug;
}

it('creates an undiscounted customer or admin order and reserves inventory exactly once', function (string $role): void {
    $buyer = $role === 'administrator' ? User::query()->where('role', 'administrator')->firstOrFail() : User::factory()->create();
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 80000, 'old_price' => 100000, 'stock' => 8]);
    Sanctum::actingAs($buyer);
    $key = (string) Str::uuid();
    $url = customerProductUrl($shop, $product).'/orders';
    $payload = customerOrderPayload(['total' => 1, 'discount_amount' => 99999]);

    $first = $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)
        ->assertCreated()->assertJsonPath('order.total', 200000)->assertJsonPath('order.discount_amount', 0);
    $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)
        ->assertCreated()->assertJsonPath('order.id', $first->json('order.id'));

    $this->assertDatabaseHas('shop_orders', ['id' => $first->json('order.id'), 'customer_id' => $buyer->id, 'total' => 200000]);
    $this->assertDatabaseCount('shop_orders', 1);
    expect($product->fresh()->stock)->toBe(6);
    expect(ShopOrder::first()->shipping_address)->toContain('Phường Hoàn Kiếm', 'Thành phố Hà Nội');

    $this->withHeader('Idempotency-Key', $key)->postJson($url, customerOrderPayload(['quantity' => 1]))->assertConflict();
})->with(['customer', 'administrator']);

it('unlocks sale pricing only after a completed ad and consumes the reward once', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create([
        'price' => 80000, 'old_price' => 100000, 'stock' => 8,
        'advertisement_video_url' => 'https://example.com/advertisement.mp4', 'advertisement_duration_seconds' => 15,
    ]);
    $base = customerProductUrl($shop, $product);
    $ad = $this->postJson($base.'/advertisement')->assertCreated()->json('id');
    $this->postJson($base.'/advertisement/'.$ad.'/complete')->assertUnprocessable()->assertJsonValidationErrors('advertisement_view_id');
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($base.'/orders', customerOrderPayload(['advertisement_view_id' => $ad]))
        ->assertUnprocessable()->assertJsonValidationErrors('advertisement_view_id');
    $this->travel(15)->seconds();
    $this->postJson($base.'/advertisement/'.$ad.'/complete')->assertOk();
    $response = $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($base.'/orders', customerOrderPayload(['advertisement_view_id' => $ad]))
        ->assertCreated()->assertJsonPath('order.subtotal', 200000)->assertJsonPath('order.discount_amount', 40000)->assertJsonPath('order.total', 160000);
    $this->assertDatabaseHas('product_ad_views', ['id' => $ad, 'shop_order_id' => $response->json('order.id')]);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($base.'/orders', customerOrderPayload(['advertisement_view_id' => $ad]))
        ->assertUnprocessable()->assertJsonValidationErrors('advertisement_view_id');
    expect($product->fresh()->stock)->toBe(6);
});

it('rejects another customers ad reward and expired rewards', function (): void {
    $buyer = User::factory()->create();
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create([
        'price' => 80000, 'old_price' => 100000, 'stock' => 8,
        'advertisement_video_url' => 'https://example.com/ad.mp4', 'advertisement_duration_seconds' => 15,
    ]);
    $view = ProductAdView::query()->create([
        'user_id' => $buyer->id, 'shop_product_id' => $product->id, 'video_url' => $product->advertisement_video_url,
        'duration_seconds' => 15, 'completed_at' => now(), 'expires_at' => now()->addHour(),
    ]);
    $url = customerProductUrl($shop, $product);
    Sanctum::actingAs(User::factory()->create());
    $this->postJson($url.'/advertisement/'.$view->id.'/complete')->assertNotFound();
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url.'/orders', customerOrderPayload(['advertisement_view_id' => $view->id]))->assertUnprocessable();
    Sanctum::actingAs($buyer);
    $this->travel(2)->hours();
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url.'/orders', customerOrderPayload(['advertisement_view_id' => $view->id]))->assertUnprocessable();
    $this->assertDatabaseCount('shop_orders', 0);
});

it('validates address membership, variant selection, and available stock', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->hasVariants()->create(['price' => 80000, 'old_price' => 100000]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['price' => 120000, 'stock' => 2]);
    Sanctum::actingAs(User::factory()->create());
    $url = customerProductUrl($shop, $product).'/orders';
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url, customerOrderPayload(['shipping_province_code' => 4]))->assertUnprocessable()->assertJsonValidationErrors('shipping_ward_code');
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url, customerOrderPayload())->assertUnprocessable()->assertJsonValidationErrors('variant_id');
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url, customerOrderPayload(['variant_id' => $variant->id, 'quantity' => 3]))->assertUnprocessable()->assertJsonValidationErrors('quantity');
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url, customerOrderPayload(['variant_id' => $variant->id]))->assertCreated()->assertJsonPath('order.total', 300000);
    expect($variant->fresh()->stock)->toBe(0);
});

it('requires authentication and prevents self-purchase or cross-shop products', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $this->postJson(customerProductUrl($shop, $product).'/orders', customerOrderPayload())->assertUnauthorized();
    Sanctum::actingAs($shop);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(customerProductUrl($shop, $product).'/orders', customerOrderPayload())->assertForbidden();
    Sanctum::actingAs(User::factory()->create());
    $otherShop = User::factory()->shopOwner()->create();
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(customerProductUrl($otherShop, $product).'/orders', customerOrderPayload())->assertNotFound();
});
