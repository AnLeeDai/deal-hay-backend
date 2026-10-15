<?php

use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('updates an existing products offer and product video without requiring seller advertisements or overwriting inventory', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 9]);
    Sanctum::actingAs($shop);
    $payload = [
        'price' => 80000, 'old_price' => 100000, 'video_url' => 'https://example.com/product.mp4',
        'stock' => 99999,
    ];

    $this->patchJson('/api/private/shop/products/'.$product->id.'/pricing', $payload)->assertOk();
    $this->assertDatabaseHas('shop_products', [
        'id' => $product->id, 'price' => 80000, 'old_price' => 100000, 'stock' => 9,
        'video_url' => 'https://example.com/product.mp4',
        'advertisement_video_url' => null, 'advertisement_duration_seconds' => null,
    ]);
});

it('clears an existing offer and product video without advertisement inputs', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create([
        'price' => 80000, 'old_price' => 100000, 'video_url' => 'https://example.com/product.mp4',
    ]);
    Sanctum::actingAs($shop);

    $this->patchJson('/api/private/shop/products/'.$product->id.'/pricing', [
        'price' => 80000, 'old_price' => null, 'video_url' => null,
    ])->assertOk();
    $this->assertDatabaseHas('shop_products', ['id' => $product->id, 'old_price' => null, 'video_url' => null]);
});

it('creates a discounted product with its own video and no seller advertisement', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $this->partialMock(MediaClient::class)->shouldReceive('upload')->once()->andReturn('products/product.webp');
    Sanctum::actingAs($shop);

    $response = $this->postJson('/api/private/shop/products', [
        'name' => 'Sản phẩm có ưu đãi', 'category' => $category->name,
        'price' => 80000, 'old_price' => 100000, 'stock' => 9,
        'video_url' => 'https://example.com/product.mp4',
        'image' => UploadedFile::fake()->image('product.jpg'),
    ])->assertCreated();

    $this->assertDatabaseHas('shop_products', [
        'id' => $response->json('data.id'), 'shop_owner_id' => $shop->id,
        'price' => 80000, 'old_price' => 100000, 'stock' => 9,
        'video_url' => 'https://example.com/product.mp4',
        'advertisement_video_url' => null, 'advertisement_duration_seconds' => null,
    ]);
    $response->assertJsonPath('data.video_url', 'https://example.com/product.mp4')
        ->assertJsonMissingPath('data.advertisement_video_url')
        ->assertJsonMissingPath('data.advertisement_duration_seconds');
});

it('updates the complete catalog product with an offer and product video without seller advertisements', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $this->partialMock(MediaClient::class)->shouldReceive('deleteLater')->once()->with([]);
    Sanctum::actingAs($shop);

    $response = $this->patchJson('/api/private/shop/products/'.$product->id, [
        'name' => $product->name, 'category' => $product->productCategory->name,
        'price' => 80000, 'old_price' => 100000, 'stock' => 9,
        'video_url' => 'https://example.com/product.mp4',
    ])->assertOk();

    $this->assertDatabaseHas('shop_products', [
        'id' => $product->id, 'price' => 80000, 'old_price' => 100000, 'stock' => 9,
        'video_url' => 'https://example.com/product.mp4',
        'advertisement_video_url' => null, 'advertisement_duration_seconds' => null,
    ]);
    $response->assertJsonPath('data.video_url', 'https://example.com/product.mp4')
        ->assertJsonMissingPath('data.advertisement_video_url')
        ->assertJsonMissingPath('data.advertisement_duration_seconds');
});

it('does not expose legacy seller advertisement settings in shop management', function (): void {
    $shop = User::factory()->shopOwner()->create();
    ShopProduct::factory()->for($shop, 'shopOwner')->create([
        'price' => 80000, 'old_price' => 100000, 'video_url' => 'https://example.com/product.mp4',
        'advertisement_video_url' => 'https://example.com/legacy-ad.mp4', 'advertisement_duration_seconds' => 15,
    ]);
    Sanctum::actingAs($shop);

    $this->getJson('/api/private/shop/manage')->assertOk()
        ->assertJsonPath('products.0.old_price', 100000)
        ->assertJsonPath('products.0.video_url', 'https://example.com/product.mp4')
        ->assertJsonMissingPath('products.0.advertisement_video_url')
        ->assertJsonMissingPath('products.0.advertisement_duration_seconds');
});

it('returns 422 for seller advertisement settings on every product write endpoint', function (string $operation, string $field, mixed $value): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 80000, 'old_price' => null, 'stock' => 9]);
    $payload = [
        'name' => $product->name, 'category' => $product->productCategory->name,
        'price' => 70000, 'old_price' => 100000, 'stock' => 12,
        'video_url' => 'https://example.com/product.mp4',
        'image' => UploadedFile::fake()->image('product.jpg'),
        $field => $value,
    ];
    [$method, $url] = match ($operation) {
        'create' => ['POST', '/api/private/shop/products'],
        'update' => ['PATCH', '/api/private/shop/products/'.$product->id],
        'pricing' => ['PATCH', '/api/private/shop/products/'.$product->id.'/pricing'],
    };
    $this->partialMock(MediaClient::class)->shouldNotReceive('upload');
    Sanctum::actingAs($shop);

    $this->json($method, $url, $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('shop_products', 1);
    $this->assertDatabaseHas('shop_products', [
        'id' => $product->id, 'price' => 80000, 'old_price' => null, 'stock' => 9,
        'video_url' => null, 'advertisement_video_url' => null, 'advertisement_duration_seconds' => null,
    ]);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
})->with(['create', 'update', 'pricing'])->with([
    'seller advertisement video' => ['advertisement_video_url', 'https://example.com/seller-ad.mp4'],
    'seller advertisement duration' => ['advertisement_duration_seconds', 15],
]);

it('rejects invalid offers and pricing edits from a different shop', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $payload = ['price' => 80000, 'old_price' => 70000, 'video_url' => null];
    Sanctum::actingAs($shop);
    $this->patchJson('/api/private/shop/products/'.$product->id.'/pricing', $payload)->assertUnprocessable()->assertJsonValidationErrors('old_price');
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
