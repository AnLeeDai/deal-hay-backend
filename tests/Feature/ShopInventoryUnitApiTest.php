<?php

use App\Models\ShopInventoryUnit;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use App\Services\ShopInventoryUnitService;
use Laravel\Sanctum\Sanctum;

it('requires an active shop owner to access physical inventory labels', function (): void {
    $this->getJson('/api/private/shop/inventory-units')->assertUnauthorized();
    Sanctum::actingAs(User::factory()->customer()->create());
    $this->getJson('/api/private/shop/inventory-units')->assertForbidden();
    Sanctum::actingAs(User::factory()->shopOwner()->create(['is_active' => false]));
    $this->getJson('/api/private/shop/inventory-units')->assertForbidden();
    $this->assertDatabaseCount('shop_inventory_units', 0);
});

it('paginates every physical unit and returns stable labels without exposing another shop', function (): void {
    $owner = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($owner, 'shopOwner')->create(['stock' => 3, 'price' => 34567]);
    $foreign = ShopProduct::factory()->create(['stock' => 2]);
    app(ShopInventoryUnitService::class)->ensureForProduct($foreign);
    Sanctum::actingAs($owner);

    $first = $this->getJson('/api/private/shop/inventory-units?per_page=2')->assertOk()
        ->assertJsonCount(2, 'data')->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 2)->assertJsonPath('meta.total', 3)->assertJsonPath('meta.per_page', 2);
    $second = $this->getJson('/api/private/shop/inventory-units?per_page=2&page=2')->assertOk()->assertJsonCount(1, 'data');
    $again = $this->getJson('/api/private/shop/inventory-units?per_page=2')->assertOk();
    expect($again->json('data'))->toBe($first->json('data'));
    $ids = [...$first->json('data.*.id'), ...$second->json('data.*.id')];
    expect(array_unique($ids))->toHaveCount(3);
    expect($first->json('data.0'))->toMatchArray([
        'shop_product_id' => $product->getKey(), 'shop_product_variant_id' => null,
        'product_name' => $product->name, 'variant_name' => null,
        'stock' => 1, 'price' => 34567, 'is_active' => true, 'status' => 'available',
    ]);
    expect($first->json('data.0.code'))->toBe($first->json('data.0.sku'));
    $this->getJson('/api/private/shop/inventory-units?product_id='.$foreign->getKey())->assertOk()->assertJsonCount(0, 'data');
    $this->assertDatabaseCount('shop_inventory_units', 5);
});

it('supports unit QR SKU and product searches while excluding retired physical stock', function (): void {
    $owner = User::factory()->shopOwner()->create();
    $hidden = ShopProduct::factory()->for($owner, 'shopOwner')->create(['name' => 'Hidden physical product', 'stock' => 2, 'is_active' => false]);
    $product = ShopProduct::factory()->for($owner, 'shopOwner')->create(['stock' => 0, 'price' => 20000]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 2, 'price' => 45678, 'is_active' => false]);
    $retired = ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 2, 'retired_at' => now()]);
    Sanctum::actingAs($owner);

    $all = $this->getJson('/api/private/shop/inventory-units')->assertOk()->assertJsonCount(4, 'data');
    expect(collect($all->json('data'))->pluck('shop_product_variant_id'))->not->toContain($retired->getKey());
    expect(collect($all->json('data'))->every(fn ($unit) => $unit['is_active'] === false))->toBeTrue();
    $unit = collect($all->json('data'))->firstWhere('shop_product_variant_id', $variant->getKey());
    expect($unit['price'])->toBe(45678);
    foreach ([$unit['sku'], $unit['code']] as $search) {
        $this->getJson('/api/private/shop/inventory-units?search='.urlencode($search))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $unit['id']);
    }
    $this->getJson('/api/private/shop/inventory-units?search=Hidden')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.shop_product_id', $hidden->getKey());
});

it('validates pagination and handles backfills beyond a single insertion batch', function (): void {
    $owner = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($owner, 'shopOwner')->create(['stock' => 1001]);
    Sanctum::actingAs($owner);
    $this->getJson('/api/private/shop/inventory-units?per_page=201')->assertUnprocessable();
    $this->getJson('/api/private/shop/inventory-units?page=0')->assertUnprocessable();
    $this->getJson('/api/private/shop/inventory-units?product_id=not-a-uuid')->assertUnprocessable();
    $this->assertDatabaseCount('shop_inventory_units', 0);

    $this->getJson('/api/private/shop/inventory-units?per_page=200&page=6')->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1001)->assertJsonPath('meta.last_page', 6);
    expect(ShopInventoryUnit::where('shop_product_id', $product->getKey())->distinct()->count('sku'))->toBe(1001);
});
