<?php

use App\Actions\UpdateShopOrderStatus;
use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function shopCouponPayload(ShopProduct $product, array $attributes = []): array
{
    return [
        'name' => 'Ưu đãi cuối tuần', 'discount_type' => 'percent', 'discount_value' => 20,
        'minimum_order_amount' => 0, 'maximum_discount_amount' => null, 'usage_limit' => null,
        'starts_at' => null, 'ends_at' => null, 'is_active' => true,
        'product_ids' => [$product->getKey()], ...$attributes,
    ];
}

function shopCouponOrderPayload(ShopCoupon $coupon, array $attributes = []): array
{
    return [
        'customer_name' => 'Người mua ưu đãi', 'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        'quantity' => 2, 'shop_coupon_code' => $coupon->code, ...$attributes,
    ];
}

function shopCouponOrderUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug.'/orders';
}

it('generates immutable unique coupon codes and only assigns the owners products', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $firstProduct = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $secondProduct = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $foreignProduct = ShopProduct::factory()->create();
    Sanctum::actingAs($shop);

    $first = $this->postJson('/api/private/shop/coupons', shopCouponPayload($firstProduct, ['ends_at' => now()->addDay()->toISOString()]))->assertCreated();
    $second = $this->postJson('/api/private/shop/coupons', shopCouponPayload($secondProduct))->assertCreated();
    $coupon = ShopCoupon::findOrFail($first->json('data.id'));
    $originalCode = $coupon->code;
    expect($originalCode)->toMatch('/^SHOP[A-F0-9]{32}$/')->not->toBe($second->json('data.code'));
    expect($coupon->products->modelKeys())->toBe([$firstProduct->id]);

    $this->patchJson('/api/private/shop/coupons/'.$coupon->id, shopCouponPayload($secondProduct, ['name' => 'Tên hiển thị mới']))
        ->assertOk()->assertJsonPath('data.code', $originalCode);
    expect($coupon->fresh()->products->modelKeys())->toBe([$secondProduct->id]);
    $this->patchJson('/api/private/shop/coupons/'.$coupon->id, shopCouponPayload($firstProduct, ['code' => 'CUSTOM']))
        ->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson('/api/private/shop/coupons', shopCouponPayload($firstProduct, ['code' => 'CUSTOM']))
        ->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->postJson('/api/private/shop/coupons', shopCouponPayload($foreignProduct))
        ->assertUnprocessable()->assertJsonValidationErrors('product_ids.0');
    $this->postJson('/api/private/shop/coupons', shopCouponPayload($firstProduct, ['product_ids' => []]))
        ->assertUnprocessable()->assertJsonValidationErrors('product_ids');
    expect($coupon->fresh()->code)->toBe($originalCode);
    $this->assertDatabaseCount('shop_coupons', 2);
});

it('only lists live shop offers assigned to visible products and respects product filtering', function (): void {
    $this->freezeTime();
    $product = ShopProduct::factory()->create(['is_active' => true]);
    $otherProduct = ShopProduct::factory()->for($product->shopOwner, 'shopOwner')->create(['is_active' => true]);
    $hiddenProduct = ShopProduct::factory()->for($product->shopOwner, 'shopOwner')->create(['is_active' => false]);
    $live = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create();
    $live->products()->attach($product);
    $other = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create();
    $other->products()->attach($otherProduct);
    foreach ([['is_active' => false], ['ends_at' => now()], ['starts_at' => now()->addMinute()], ['usage_limit' => 1, 'used_count' => 1]] as $state) {
        $coupon = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create($state);
        $coupon->products()->attach($product);
    }
    $hidden = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create();
    $hidden->products()->attach($hiddenProduct);

    $url = '/api/shops/'.$product->shopOwner->user_code.'/coupons';
    $this->getJson($url)->assertOk()->assertJsonCount(2, 'coupons');
    $this->getJson($url.'?product_id='.$product->id)->assertOk()->assertJsonCount(1, 'coupons')
        ->assertJsonPath('coupons.0.id', $live->id)->assertJsonPath('coupons.0.products.0.id', $product->id);
    $this->getJson($url.'?product_id=invalid')->assertUnprocessable();
    $this->getJson('/api/shops/'.User::factory()->create()->user_code.'/coupons')->assertNotFound();
});

it('applies the shop offer once and restores its quota when the order is cancelled', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $coupon = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create(['discount_type' => 'percent', 'discount_value' => 20, 'maximum_discount_amount' => 30000, 'usage_limit' => 1]);
    $coupon->products()->attach($product);
    Sanctum::actingAs($buyer);
    $key = (string) Str::uuid();
    $response = $this->withHeader('Idempotency-Key', $key)->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon))
        ->assertCreated()->assertJsonPath('order.discount_amount', 30000)->assertJsonPath('order.total', 170000);
    $this->withHeader('Idempotency-Key', $key)->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon))
        ->assertCreated()->assertJsonPath('order.id', $response->json('order.id'));
    expect($coupon->fresh()->used_count)->toBe(1);
    expect($product->fresh()->stock)->toBe(6);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon))
        ->assertUnprocessable()->assertJsonValidationErrors('shop_coupon_code');
    $this->withHeader('Idempotency-Key', $key)->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon, ['shop_coupon_code' => null]))->assertConflict();
    app(UpdateShopOrderStatus::class)->handle($product->shopOwner, ShopOrder::findOrFail($response->json('order.id')), 'cancelled');
    expect($coupon->fresh()->used_count)->toBe(0);
    expect($product->fresh()->stock)->toBe(8);
});

it('rejects ineligible shop coupons without changing stock or quota', function (string $failure): void {
    $this->freezeTime();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $coupon = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create(match ($failure) {
        'expired' => ['ends_at' => now()],
        'future' => ['starts_at' => now()->addMinute()],
        'minimum' => ['minimum_order_amount' => 300000],
        'quota' => ['usage_limit' => 1, 'used_count' => 1],
        'inactive' => ['is_active' => false],
        default => [],
    });
    $coupon->products()->attach($failure === 'product' ? ShopProduct::factory()->for($product->shopOwner, 'shopOwner')->create() : $product);
    if ($failure === 'shop') {
        $coupon->update(['shop_owner_id' => User::factory()->shopOwner()->create()->id]);
    }
    $originalCount = $coupon->used_count;
    Sanctum::actingAs(User::factory()->customer()->create());
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon))
        ->assertUnprocessable()->assertJsonValidationErrors('shop_coupon_code');
    expect($coupon->fresh()->used_count)->toBe($originalCount);
    expect($product->fresh()->stock)->toBe(8);
    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
})->with(['expired', 'future', 'minimum', 'quota', 'inactive', 'product', 'shop']);

it('stacks a shop percentage after wallet credit and clamps fixed offers to remaining money', function (): void {
    $buyer = User::factory()->customer()->create();
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $coupon = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create(['discount_type' => 'percent', 'discount_value' => 20]);
    $coupon->products()->attach($product);
    $view = ProductAdView::query()->create([
        'user_id' => $buyer->id, 'shop_product_id' => $product->id, 'provider' => 'verified_fixture', 'campaign_id' => 'fixed-credit', 'ad_type' => 'rewarded',
        'reward_type' => 'fixed', 'reward_value' => 50000, 'video_url' => '', 'duration_seconds' => 0,
        'completed_at' => now(), 'verified_at' => now(), 'verification_reference' => (string) Str::uuid(), 'expires_at' => now()->addHour(),
    ]);
    $wallet = ProductDiscountCode::query()->create([
        'user_id' => $buyer->id, 'shop_product_id' => $product->id, 'product_ad_view_id' => $view->id, 'code' => Str::upper(Str::random(32)),
        'reward_type' => 'fixed', 'reward_value' => 50000, 'original_price' => 1, 'sale_price' => 1, 'saved_at' => now(), 'expires_at' => now()->addDay(),
    ]);
    Sanctum::actingAs($buyer);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon, ['discount_codes' => [$wallet->code]]))
        ->assertCreated()->assertJsonPath('order.discount_amount', 80000)->assertJsonPath('order.total', 120000);
    $coupon->update(['discount_type' => 'fixed', 'discount_value' => 999999999]);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon))
        ->assertCreated()->assertJsonPath('order.discount_amount', 200000)->assertJsonPath('order.total', 0);
});

it('only discounts assigned order lines in manually entered shop orders', function (): void {
    $product = ShopProduct::factory()->create(['price' => 100000, 'old_price' => null, 'stock' => 8]);
    $other = ShopProduct::factory()->for($product->shopOwner, 'shopOwner')->create(['price' => 200000, 'stock' => 8]);
    $coupon = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create(['discount_type' => 'percent', 'discount_value' => 20]);
    $coupon->products()->attach($product);
    Sanctum::actingAs($product->shopOwner);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/private/shop/orders', [
        ...shopCouponOrderPayload($coupon), 'coupon_code' => $coupon->code,
        'items' => [['sku' => $product->sku, 'quantity' => 1], ['sku' => $other->sku, 'quantity' => 1]],
    ])->assertCreated();
    expect(ShopOrder::first()->discount_amount)->toBe(20000);
    expect(ShopOrder::first()->total)->toBe(280000);
});

it('does not consume a shop coupon when its percentage rounds down to no additional discount', function (): void {
    $product = ShopProduct::factory()->create(['price' => 1, 'old_price' => null, 'stock' => 8]);
    $coupon = ShopCoupon::factory()->for($product->shopOwner, 'shopOwner')->create(['discount_type' => 'percent', 'discount_value' => 1]);
    $coupon->products()->attach($product);
    Sanctum::actingAs(User::factory()->customer()->create());
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shopCouponOrderUrl($product), shopCouponOrderPayload($coupon, ['quantity' => 1]))
        ->assertUnprocessable()->assertJsonValidationErrors('shop_coupon_code');
    expect($coupon->fresh()->used_count)->toBe(0);
    expect($product->fresh()->stock)->toBe(8);
    $this->assertDatabaseCount('shop_orders', 0);
});
