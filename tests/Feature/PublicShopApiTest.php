<?php

use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\User;

it('identifies a bearer token customer on the public shop endpoint', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('public-shop-test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/shops/'.$shop->user_code)
        ->assertOk()
        ->assertJsonPath('shop.viewer.can_interact', true);
});

it('identifies a bearer token customer on the public product endpoint', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $product = ShopProduct::factory()
        ->for($shop, 'shopOwner')
        ->for($category, 'productCategory')
        ->create();
    $customer = User::factory()->customer()->create();
    $token = $customer->createToken('public-product-test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/shops/'.$shop->user_code.'/products/'.$product->slug)
        ->assertOk()
        ->assertJsonPath('shop.viewer.can_interact', true);
});

it('keeps public product access anonymous without a bearer token', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $product = ShopProduct::factory()
        ->for($shop, 'shopOwner')
        ->for($category, 'productCategory')
        ->create();

    $this->getJson('/api/shops/'.$shop->user_code.'/products/'.$product->slug)
        ->assertOk()
        ->assertJsonPath('shop.viewer.can_interact', false);
});

it('prevents a shop owner from interacting with their own public shop', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $token = $shop->createToken('own-public-shop-test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/shops/'.$shop->user_code)
        ->assertOk()
        ->assertJsonPath('shop.viewer.can_interact', false);
});
