<?php

use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\ShippingDiscountCode;
use App\Models\ShippingRewardView;
use App\Models\ShopProduct;
use App\Models\ShopShippingSetting;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config()->set('shipping_rewards.enabled', true);
});

function shippingRewardProduct(array $settings = []): ShopProduct
{
    $setting = ShopShippingSetting::factory()->create(['enabled' => true, 'base_fee' => 30000, ...$settings]);

    return ShopProduct::factory()->for($setting->shopOwner, 'shopOwner')->create(['price' => 100000, 'stock' => 5]);
}

function shippingRewardUrl(ShopProduct $product): string
{
    return '/api/shops/'.$product->shopOwner->user_code.'/products/'.$product->slug;
}

function shippingRewardOrderPayload(ShippingDiscountCode $code, array $overrides = []): array
{
    return [
        'quantity' => 1, 'customer_name' => 'Người nhận', 'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70,
        'quoted_shipping_fee' => 30000, 'shipping_discount_code' => $code->code, ...$overrides,
    ];
}

it('returns an unavailable offer and 422 without minting when promotion or shipping is disabled', function (bool $enabled, bool $configured, string $reason): void {
    config()->set('shipping_rewards.enabled', $enabled);
    $product = shippingRewardProduct(['enabled' => $configured]);
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->getJson(shippingRewardUrl($product).'/shipping-offer')->assertOk()->assertExactJson([
        'shipping_offer' => ['available' => false, 'unavailable_reason' => $reason],
    ]);
    $this->postJson(shippingRewardUrl($product).'/shipping-offer/views')->assertUnprocessable()
        ->assertJsonValidationErrors('shipping_offer');

    $this->assertDatabaseCount('shipping_reward_views', 0);
    $this->assertDatabaseCount('shipping_discount_codes', 0);
})->with([
    'promotion disabled' => [false, true, 'promotion_disabled'],
    'shipping disabled' => [true, false, 'shipping_not_configured'],
]);

it('publishes an available offer and requires authentication for starting redeeming and retrieving codes', function (): void {
    $product = shippingRewardProduct();
    $view = ShippingRewardView::factory()->create(['shop_product_id' => $product->id]);
    $base = shippingRewardUrl($product).'/shipping-offer';

    $this->getJson($base)->assertOk()->assertExactJson([
        'shipping_offer' => ['available' => true, 'unavailable_reason' => null],
    ]);
    $this->postJson($base.'/views')->assertUnauthorized();
    $this->postJson($base.'/views/'.$view->id.'/redeem')->assertUnauthorized();
    $this->getJson($base.'/code')->assertUnauthorized();

    $this->assertDatabaseCount('shipping_discount_codes', 0);
});

it('returns 404 for mismatched shops and inactive product offers', function (): void {
    $product = shippingRewardProduct();
    $otherShop = User::factory()->shopOwner()->create();

    $this->getJson('/api/shops/'.$otherShop->user_code.'/products/'.$product->slug.'/shipping-offer')->assertNotFound();
    $product->update(['is_active' => false]);
    $this->getJson(shippingRewardUrl($product).'/shipping-offer')->assertNotFound();
});

it('returns 403 when the shop owner starts their own offer or an inactive customer requests it', function (): void {
    $product = shippingRewardProduct();
    Sanctum::actingAs($product->shopOwner);

    $this->postJson(shippingRewardUrl($product).'/shipping-offer/views')->assertForbidden();
    Sanctum::actingAs(User::factory()->customer()->create(['is_active' => false]));
    $this->postJson(shippingRewardUrl($product).'/shipping-offer/views')->assertForbidden();

    $this->assertDatabaseCount('shipping_reward_views', 0);
});

it('starts an opaque expiring session without granting a coupon or accepting client reward claims', function (): void {
    $this->freezeTime();
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    Sanctum::actingAs($buyer);

    $response = $this->postJson(shippingRewardUrl($product).'/shipping-offer/views', [
        'user_id' => $product->shop_owner_id, 'completed' => true, 'verified_at' => now()->toISOString(),
        'shipping_discount_code_id' => Str::uuid(),
    ])->assertCreated()->assertJsonPath('view.expires_at', now()->addMinutes(15)->startOfSecond()->toISOString());

    $this->assertDatabaseHas('shipping_reward_views', [
        'id' => $response->json('view.id'), 'user_id' => $buyer->id,
        'shop_product_id' => $product->id, 'redeemed_at' => null, 'shipping_discount_code_id' => null,
    ]);
    $this->assertDatabaseCount('shipping_discount_codes', 0);
    $this->assertDatabaseCount('product_ad_views', 0);
    $this->assertDatabaseCount('rewarded_task_conversions', 0);
});

it('redeems once per session and reuses the current product coupon across new sessions and reloads', function (): void {
    $this->freezeTime();
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    $first = ShippingRewardView::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id]);
    $second = ShippingRewardView::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id]);
    $base = shippingRewardUrl($product).'/shipping-offer';
    Sanctum::actingAs($buyer);

    $response = $this->postJson($base.'/views/'.$first->id.'/redeem')->assertOk()
        ->assertJsonPath('shipping_discount_code.is_available', true)
        ->assertJsonPath('shipping_discount_code.discount_percent', 100)
        ->assertJsonPath('shipping_discount_code.product_id', $product->id)
        ->assertJsonPath('shipping_discount_code.expires_at', now()->addDays(7)->startOfSecond()->toISOString());
    $codeId = $response->json('shipping_discount_code.id');
    $this->postJson($base.'/views/'.$first->id.'/redeem')->assertOk()->assertJsonPath('shipping_discount_code.id', $codeId);
    $this->postJson($base.'/views/'.$second->id.'/redeem')->assertOk()->assertJsonPath('shipping_discount_code.id', $codeId);
    $this->getJson($base.'/code')->assertOk()->assertJsonPath('shipping_discount_code.id', $codeId);
    $this->travel(16)->minutes();
    $this->postJson($base.'/views/'.$first->id.'/redeem')->assertOk()->assertJsonPath('shipping_discount_code.id', $codeId);

    $this->assertDatabaseCount('shipping_discount_codes', 1);
    $this->assertDatabaseHas('shipping_reward_views', ['id' => $first->id, 'shipping_discount_code_id' => $codeId]);
    $this->assertDatabaseHas('shipping_reward_views', ['id' => $second->id, 'shipping_discount_code_id' => $codeId]);
    $this->assertDatabaseCount('product_ad_views', 0);
});

it('returns 404 when redeeming another users session or a session on another product', function (bool $wrongUser): void {
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    $view = ShippingRewardView::factory()->create([
        'user_id' => $wrongUser ? User::factory()->customer()->create()->id : $buyer->id,
        'shop_product_id' => $wrongUser ? $product->id : ShopProduct::factory()->for($product->shopOwner, 'shopOwner')->create()->id,
    ]);
    Sanctum::actingAs($buyer);

    $this->postJson(shippingRewardUrl($product).'/shipping-offer/views/'.$view->id.'/redeem')->assertNotFound();

    $this->assertDatabaseCount('shipping_discount_codes', 0);
    expect($view->fresh()->redeemed_at)->toBeNull();
})->with(['wrong user' => true, 'wrong product' => false]);

it('returns 422 without granting for an expired session or a newly disabled offer', function (bool $expired): void {
    $this->freezeTime();
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    $view = ShippingRewardView::factory()->create([
        'user_id' => $buyer->id, 'shop_product_id' => $product->id,
        'expires_at' => $expired ? now() : now()->addMinutes(15),
    ]);
    config()->set('shipping_rewards.enabled', $expired);
    Sanctum::actingAs($buyer);

    $this->postJson(shippingRewardUrl($product).'/shipping-offer/views/'.$view->id.'/redeem')
        ->assertUnprocessable()->assertJsonValidationErrors('shipping_offer');

    $this->assertDatabaseCount('shipping_discount_codes', 0);
})->with(['expired session' => true, 'disabled promotion' => false]);

it('returns only the current buyers active product coupon even after new grants are disabled', function (): void {
    $this->freezeTime();
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    ShippingDiscountCode::factory()->create(['shop_product_id' => $product->id]);
    ShippingDiscountCode::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id, 'expires_at' => now()]);
    $base = shippingRewardUrl($product).'/shipping-offer/code';
    Sanctum::actingAs($buyer);

    $this->getJson($base)->assertOk()->assertExactJson(['shipping_discount_code' => null]);
    $code = ShippingDiscountCode::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id]);
    config()->set('shipping_rewards.enabled', false);
    $this->getJson($base)->assertOk()->assertJsonPath('shipping_discount_code.id', $code->id);
});

it('stacks merchandise and shipping discounts atomically and replays accepted checkout without consuming twice', function (): void {
    $this->freezeTime();
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    $shipping = ShippingDiscountCode::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id]);
    $view = ProductAdView::query()->create([
        'user_id' => $buyer->id, 'shop_product_id' => $product->id, 'video_url' => '', 'duration_seconds' => 0,
        'completed_at' => now(), 'expires_at' => now()->addDay(),
    ]);
    $merchandise = ProductDiscountCode::query()->create([
        'user_id' => $buyer->id, 'shop_product_id' => $product->id, 'product_ad_view_id' => $view->id,
        'code' => Str::upper(Str::random(32)), 'original_price' => 100000, 'sale_price' => 90000,
        'reward_type' => 'fixed', 'reward_value' => 10000, 'saved_at' => now(), 'expires_at' => now()->addDay(),
    ]);
    $key = (string) Str::uuid();
    $payload = shippingRewardOrderPayload($shipping, ['discount_codes' => [$merchandise->code]]);
    $url = shippingRewardUrl($product).'/orders';
    Sanctum::actingAs($buyer);

    $response = $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)->assertCreated()
        ->assertJsonPath('order.subtotal', 100000)->assertJsonPath('order.discount_amount', 10000)
        ->assertJsonPath('order.shipping_fee', 30000)->assertJsonPath('order.shipping_discount_amount', 30000)
        ->assertJsonPath('order.shipping_total', 0)->assertJsonPath('order.total', 90000)
        ->assertJsonPath('order.redeemed_shipping_discount_code', $shipping->code);
    ShopShippingSetting::query()->where('shop_owner_id', $product->shop_owner_id)->update(['base_fee' => 40000]);
    $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)->assertCreated()->assertJsonPath('order.id', $response->json('order.id'));
    $changed = $payload;
    unset($changed['shipping_discount_code']);
    $this->withHeader('Idempotency-Key', $key)->postJson($url, $changed)->assertConflict();

    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
    $this->assertDatabaseHas('shipping_discount_codes', ['id' => $shipping->id, 'shop_order_id' => $response->json('order.id')]);
    expect($shipping->fresh()->used_at)->not->toBeNull();
    expect($merchandise->fresh()->used_at)->not->toBeNull();
    expect($product->fresh()->stock)->toBe(4);
});

it('returns 422 without reserving stock for invalid shipping coupons', function (string $failure): void {
    $this->freezeTime();
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    $code = ShippingDiscountCode::factory()->create([
        'user_id' => $failure === 'wrong owner' ? User::factory()->customer()->create()->id : $buyer->id,
        'shop_product_id' => $failure === 'wrong product' ? ShopProduct::factory()->create()->id : $product->id,
        'expires_at' => $failure === 'expired' ? now() : now()->addDay(),
        'used_at' => $failure === 'used' ? now() : null,
    ]);
    $payload = shippingRewardOrderPayload($code, ['shipping_discount_code' => $failure === 'unknown' ? str_repeat('Z', 32) : $code->code]);
    Sanctum::actingAs($buyer);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shippingRewardUrl($product).'/orders', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('shipping_discount_code');

    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(5);
})->with(['wrong owner', 'wrong product', 'expired', 'used', 'unknown']);

it('returns 422 without consuming a coupon when shipping is free unconfigured or outside Hanoi', function (bool $enabled, int $fee, int $province, int $ward): void {
    $product = shippingRewardProduct(['enabled' => $enabled, 'base_fee' => $fee]);
    $buyer = User::factory()->customer()->create();
    $code = ShippingDiscountCode::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id]);
    Sanctum::actingAs($buyer);
    $payload = shippingRewardOrderPayload($code, [
        'quoted_shipping_fee' => $enabled && $province === 1 ? $fee : null,
        'shipping_province_code' => $province, 'shipping_ward_code' => $ward,
    ]);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shippingRewardUrl($product).'/orders', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('shipping_discount_code');

    expect($code->fresh()->used_at)->toBeNull();
    $this->assertDatabaseCount('shop_orders', 0);
})->with([
    'free fee' => [true, 0, 1, 70],
    'unconfigured shipping' => [false, 30000, 1, 70],
    'outside Hanoi' => [true, 30000, 4, 1273],
]);

it('uses the current fee after a rate change and rejects reuse on a second order', function (): void {
    $product = shippingRewardProduct(['base_fee' => 45000]);
    $buyer = User::factory()->customer()->create();
    $code = ShippingDiscountCode::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id]);
    config()->set('shipping_rewards.enabled', false);
    Sanctum::actingAs($buyer);
    $url = shippingRewardUrl($product).'/orders';

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url, shippingRewardOrderPayload($code))
        ->assertUnprocessable()->assertJsonValidationErrors('quoted_shipping_fee');
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url, shippingRewardOrderPayload($code, ['quoted_shipping_fee' => 45000]))
        ->assertCreated()->assertJsonPath('order.shipping_discount_amount', 45000)->assertJsonPath('order.total', 100000);
    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson($url, shippingRewardOrderPayload($code, ['quoted_shipping_fee' => 45000]))
        ->assertUnprocessable()->assertJsonValidationErrors('shipping_discount_code');

    $this->assertDatabaseCount('shop_orders', 1);
    expect($product->fresh()->stock)->toBe(4);
});

it('returns 422 before consuming a shipping coupon when the current quote is missing', function (): void {
    $product = shippingRewardProduct();
    $buyer = User::factory()->customer()->create();
    $code = ShippingDiscountCode::factory()->create(['user_id' => $buyer->id, 'shop_product_id' => $product->id]);
    $payload = shippingRewardOrderPayload($code, ['quoted_shipping_fee' => null]);
    Sanctum::actingAs($buyer);

    $this->withHeader('Idempotency-Key', (string) Str::uuid())->postJson(shippingRewardUrl($product).'/orders', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('quoted_shipping_fee');

    expect($code->fresh()->used_at)->toBeNull();
    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
});

it('applies configured session and coupon expiration periods', function (): void {
    $this->freezeTime();
    config()->set('shipping_rewards.session_minutes', 5);
    config()->set('shipping_rewards.coupon_days', 2);
    $product = shippingRewardProduct();
    Sanctum::actingAs(User::factory()->customer()->create());
    $base = shippingRewardUrl($product).'/shipping-offer/views';

    $response = $this->postJson($base)->assertCreated()->assertJsonPath('view.expires_at', now()->addMinutes(5)->startOfSecond()->toISOString());
    $this->postJson($base.'/'.$response->json('view.id').'/redeem')->assertOk()
        ->assertJsonPath('shipping_discount_code.expires_at', now()->addDays(2)->startOfSecond()->toISOString());

    $this->assertDatabaseCount('shipping_discount_codes', 1);
});
