<?php

use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('updates an existing products offer and video without overwriting its inventory', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 9]);
    Sanctum::actingAs($shop);
    $payload = [
        'price' => 80000, 'old_price' => 100000, 'video_url' => 'https://example.com/product.mp4',
        'advertisement_video_url' => 'https://example.com/ad.mp4', 'advertisement_duration_seconds' => 15,
        'stock' => 99999,
    ];

    $this->patchJson('/api/private/shop/products/'.$product->id.'/pricing', $payload)->assertOk();
    $this->assertDatabaseHas('shop_products', ['id' => $product->id, 'price' => 80000, 'old_price' => 100000, 'stock' => 9]);
    $this->getJson('/api/private/shop/manage')->assertOk()->assertJsonPath('products.0.old_price', 100000)->assertJsonPath('products.0.advertisement_duration_seconds', 15);
    $this->getJson('/api/shops/'.$shop->user_code.'/products/'.$product->slug)->assertOk()
        ->assertJsonPath('product.display_original_price', 100000)->assertJsonPath('product.advertisement_available', true);
    $this->patchJson('/api/private/shop/products/'.$product->id.'/pricing', [
        ...$payload, 'old_price' => null, 'video_url' => null, 'advertisement_video_url' => null, 'advertisement_duration_seconds' => null,
    ])->assertOk();
    expect($product->fresh()->old_price)->toBeNull();
    expect($product->fresh()->hasAdvertisement())->toBeFalse();
});

it('rejects invalid offers and pricing edits from a different shop', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $payload = ['price' => 80000, 'old_price' => 70000, 'video_url' => null, 'advertisement_video_url' => null, 'advertisement_duration_seconds' => null];
    Sanctum::actingAs($shop);
    $this->patchJson('/api/private/shop/products/'.$product->id.'/pricing', $payload)->assertUnprocessable()->assertJsonValidationErrors(['old_price', 'advertisement_video_url']);
    Sanctum::actingAs(User::factory()->shopOwner()->create());
    $this->patchJson('/api/private/shop/products/'.$product->id.'/pricing', $payload)->assertForbidden();
});

it('rejects an invalid delivery phone before reserving inventory', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    Sanctum::actingAs(User::factory()->create());
    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson('/api/shops/'.$shop->user_code.'/products/'.$product->slug.'/orders', [
            'quantity' => 1, 'customer_name' => 'Nguyễn A', 'customer_phone' => 'invalid',
            'shipping_address' => '12 Phố Huế', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        ])->assertUnprocessable()->assertJsonValidationErrors('customer_phone');
    $this->assertDatabaseCount('shop_orders', 0);
    expect($product->fresh()->stock)->toBe($product->stock);
});
