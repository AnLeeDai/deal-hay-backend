<?php

use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('displays the effective initial variant price without replacing the inherited base price', function (array $variants, int $displayPrice): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 50_000]);

    foreach ($variants as $position => $variant) {
        ShopProductVariant::factory()->for($product, 'product')->create([
            'stock' => 2,
            'position' => $position,
            ...$variant,
        ]);
    }

    $this->getJson('/api/shops/'.$shop->user_code.'/products/'.$product->slug)
        ->assertOk()
        ->assertJsonPath('product.price', 50_000)
        ->assertJsonPath('product.display_price', $displayPrice);
})->with([
    'simple product' => [[], 50_000],
    'preferred variant is more expensive than the base' => [[
        ['price' => 60_000],
        ['price' => 80_000, 'is_default' => true],
    ], 80_000],
    'preferred variant inherits the base' => [[
        ['price' => 60_000],
        ['price' => null, 'is_default' => true],
    ], 50_000],
    'zero price is not replaced by the base' => [[
        ['price' => 0, 'is_default' => true],
    ], 0],
    'available variant precedes a sold-out default' => [[
        ['price' => 80_000, 'stock' => 0, 'is_default' => true],
        ['price' => 60_000],
    ], 60_000],
    'sold-out default remains the initial consultation variant' => [[
        ['price' => 60_000, 'stock' => 0],
        ['price' => 80_000, 'stock' => 0, 'is_default' => true],
    ], 80_000],
    'first sold-out variant is used when there is no default' => [[
        ['price' => 60_000, 'stock' => 0],
        ['price' => 80_000, 'stock' => 0],
    ], 60_000],
    'inactive and retired variants do not affect the price' => [[
        ['price' => 1_000, 'is_default' => true, 'is_active' => false],
        ['price' => 2_000, 'is_default' => true, 'retired_at' => '2026-01-01 00:00:00'],
        ['price' => 60_000],
    ], 60_000],
    'no active variants falls back to the base' => [[
        ['price' => 1_000, 'is_active' => false],
    ], 50_000],
]);

it('uses the same variant display price on catalog cards', function (string $page): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 50_000]);
    ShopProductVariant::factory()->for($product, 'product')->default()->create(['price' => 80_000]);
    $reference = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    [$url, $productsPath] = match ($page) {
        'home' => ['/api/', 'products.data'],
        'shop' => ['/api/shops/'.$shop->user_code, 'products'],
        'related' => ['/api/shops/'.$shop->user_code.'/products/'.$reference->slug, 'relatedProducts'],
    };

    $response = $this->getJson($url)->assertOk();
    $index = array_search($product->id, array_column($response->json($productsPath), 'id'), true);

    expect($index)->not->toBeFalse();
    $response->assertJsonPath($productsPath.'.'.$index.'.price', 50_000)
        ->assertJsonPath($productsPath.'.'.$index.'.display_price', 80_000);
})->with(['home', 'shop', 'related']);

it('loads catalog variant prices in one query for multiple products', function (string $page): void {
    $shop = User::factory()->shopOwner()->create();
    $products = ShopProduct::factory()->count(3)->for($shop, 'shopOwner')->create();
    foreach ($products as $product) {
        ShopProductVariant::factory()->for($product, 'product')->create(['price' => 80_000]);
    }
    $url = $page === 'home' ? '/api/' : '/api/shops/'.$shop->user_code;
    DB::enableQueryLog();

    try {
        $this->getJson($url)->assertOk();
        $variantQueries = collect(DB::getQueryLog())->filter(
            fn (array $query): bool => str_contains($query['query'], 'shop_product_variants'),
        );
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($variantQueries)->toHaveCount(1);
})->with(['home', 'shop']);
