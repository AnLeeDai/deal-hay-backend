<?php

use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopShippingSetting;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function hanoiShippingProductUrl(User $shop, ShopProduct $product): string
{
    return '/api/shops/'.$shop->user_code.'/products/'.$product->slug;
}

function hanoiShippingOrderPayload(array $overrides = []): array
{
    return [
        'quantity' => 1,
        'customer_name' => 'Người nhận',
        'customer_phone' => '0901234567',
        'shipping_address' => '12 Hàng Bài',
        'shipping_province_code' => 1,
        'shipping_ward_code' => 70,
        ...$overrides,
    ];
}

it('requires active shop owners to read and update shipping settings', function (): void {
    $payload = ['enabled' => true, 'base_fee' => 30000, 'ward_rates' => []];
    $this->getJson('/api/shop/shipping-settings')->assertUnauthorized();
    $this->putJson('/api/shop/shipping-settings', $payload)->assertUnauthorized();

    Sanctum::actingAs(User::factory()->customer()->create());
    $this->getJson('/api/shop/shipping-settings')->assertForbidden();
    $this->putJson('/api/shop/shipping-settings', $payload)->assertForbidden();

    Sanctum::actingAs(User::factory()->shopOwner()->create(['is_active' => false]));
    $this->putJson('/api/shop/shipping-settings', $payload)->assertForbidden();
    $this->assertDatabaseCount('shop_shipping_settings', 0);
});

it('defaults to disabled rates without inventing or persisting a shipping price', function (): void {
    Sanctum::actingAs(User::factory()->shopOwner()->create());
    $this->getJson('/api/shop/shipping-settings')->assertOk()->assertExactJson([
        'shipping_settings' => ['enabled' => false, 'base_fee' => 0, 'ward_rates' => []],
    ]);
    $this->assertDatabaseCount('shop_shipping_settings', 0);
});

it('saves only the current shops settings and normalizes complete ward rates', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $otherSetting = ShopShippingSetting::factory()->create(['enabled' => true, 'base_fee' => 40000]);
    Sanctum::actingAs($shop);

    $this->putJson('/api/shop/shipping-settings', [
        'enabled' => true, 'base_fee' => '30000', 'shop_owner_id' => $otherSetting->shop_owner_id,
        'ward_rates' => [['ward_code' => '166', 'fee' => '35000'], ['ward_code' => '70', 'fee' => '20000']],
    ])->assertOk()->assertJsonPath('shipping_settings', [
        'enabled' => true, 'base_fee' => 30000,
        'ward_rates' => [['ward_code' => 70, 'fee' => 20000], ['ward_code' => 166, 'fee' => 35000]],
    ]);

    $this->assertDatabaseHas('shop_shipping_settings', ['shop_owner_id' => $shop->id, 'base_fee' => 30000]);
    expect($otherSetting->fresh()->base_fee)->toBe(40000);
    $this->putJson('/api/shop/shipping-settings', ['enabled' => false, 'base_fee' => 0, 'ward_rates' => []])->assertOk();
    expect(ShopShippingSetting::query()->where('shop_owner_id', $shop->id)->firstOrFail()->ward_rates)->toBe([]);
});

it('returns 422 for invalid fees duplicate wards and wards outside Hanoi', function (array $overrides, array $errors): void {
    Sanctum::actingAs(User::factory()->shopOwner()->create());
    $this->putJson('/api/shop/shipping-settings', [
        'enabled' => true, 'base_fee' => 30000, 'ward_rates' => [], ...$overrides,
    ])->assertUnprocessable()->assertJsonValidationErrors($errors);
    $this->assertDatabaseCount('shop_shipping_settings', 0);
})->with([
    'negative fee' => [['base_fee' => -1], ['base_fee']],
    'fractional fee' => [['base_fee' => 1.5], ['base_fee']],
    'excessive fee' => [['base_fee' => 1000000000], ['base_fee']],
    'negative ward fee' => [['ward_rates' => [['ward_code' => 70, 'fee' => -1]]], ['ward_rates.0.fee']],
    'duplicate ward' => [['ward_rates' => [['ward_code' => 70, 'fee' => 0], ['ward_code' => '70', 'fee' => 1]]], ['ward_rates.0.ward_code']],
    'ward outside Hanoi' => [['ward_rates' => [['ward_code' => 1273, 'fee' => 1000]]], ['ward_rates.0.ward_code']],
    'unknown nested key' => [['ward_rates' => [['ward_code' => 70, 'fee' => 1000, 'discount' => 100]]], ['ward_rates.0']],
]);

it('quotes the exact ward override or base fee publicly without issuing any reward', function (int $wardCode, int $fee): void {
    $setting = ShopShippingSetting::factory()->create([
        'enabled' => true, 'base_fee' => 30000, 'ward_rates' => [['ward_code' => 70, 'fee' => 20000], ['ward_code' => 175, 'fee' => 0]],
    ]);
    $shop = $setting->shopOwner;
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();

    $this->postJson(hanoiShippingProductUrl($shop, $product).'/shipping-quote', [
        'shipping_province_code' => 1, 'shipping_ward_code' => $wardCode,
    ])->assertOk()->assertExactJson(['shipping_quote' => [
        'eligible' => true, 'configured' => true, 'fee' => $fee, 'province_code' => 1, 'ward_code' => $wardCode, 'reason' => null,
    ]]);
    $this->assertDatabaseCount('product_discount_codes', 0);
    $this->assertDatabaseCount('product_ad_views', 0);
})->with(['ward override' => [70, 20000], 'base rate' => [166, 30000], 'explicit free ward' => [175, 0]]);

it('quotes null rather than free shipping when settings are missing or destination is outside Hanoi', function (bool $configured, int $provinceCode, int $wardCode, string $reason): void {
    $shop = User::factory()->shopOwner()->create();
    if ($configured) {
        ShopShippingSetting::factory()->for($shop, 'shopOwner')->create(['enabled' => true, 'base_fee' => 30000]);
    }
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();

    $this->postJson(hanoiShippingProductUrl($shop, $product).'/shipping-quote', [
        'shipping_province_code' => $provinceCode, 'shipping_ward_code' => $wardCode,
    ])->assertOk()->assertJsonPath('shipping_quote.fee', null)
        ->assertJsonPath('shipping_quote.configured', $configured)
        ->assertJsonPath('shipping_quote.eligible', $provinceCode === 1)
        ->assertJsonPath('shipping_quote.reason', $reason);
})->with(['unconfigured' => [false, 1, 70, 'not_configured'], 'outside Hanoi' => [true, 4, 1273, 'outside_hanoi']]);

it('returns 422 for inconsistent quote addresses and 404 for hidden or mismatched products', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $this->postJson(hanoiShippingProductUrl($shop, $product).'/shipping-quote', [
        'shipping_province_code' => 4, 'shipping_ward_code' => 70,
    ])->assertUnprocessable()->assertJsonValidationErrors('shipping_ward_code');

    $otherShop = User::factory()->shopOwner()->create();
    $payload = ['shipping_province_code' => 1, 'shipping_ward_code' => 70];
    $this->postJson(hanoiShippingProductUrl($otherShop, $product).'/shipping-quote', $payload)->assertNotFound();
    $product->update(['is_active' => false]);
    $this->postJson(hanoiShippingProductUrl($shop, $product).'/shipping-quote', $payload)->assertNotFound();
});

it('charges authoritative shipping once and preserves the accepted quote on idempotent retry after rate changes', function (): void {
    $setting = ShopShippingSetting::factory()->create(['enabled' => true, 'base_fee' => 30000, 'ward_rates' => [['ward_code' => 70, 'fee' => 20000]]]);
    $shop = $setting->shopOwner;
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 100000, 'stock' => 5]);
    Sanctum::actingAs(User::factory()->customer()->create());
    $key = (string) Str::uuid();
    $payload = hanoiShippingOrderPayload(['quoted_shipping_fee' => 20000, 'shipping_discount_amount' => 20000, 'total' => 1]);
    $url = hanoiShippingProductUrl($shop, $product).'/orders';

    $first = $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)->assertCreated()
        ->assertJsonPath('order.shipping_fee', 20000)->assertJsonPath('order.shipping_total', 20000)
        ->assertJsonPath('order.shipping_discount_amount', 0)->assertJsonPath('order.total', 120000);
    $setting->update(['ward_rates' => [['ward_code' => 70, 'fee' => 25000]]]);
    $this->withHeader('Idempotency-Key', $key)->postJson($url, $payload)->assertCreated()
        ->assertJsonPath('order.id', $first->json('order.id'))->assertJsonPath('order.total', 120000);
    $this->withHeader('Idempotency-Key', $key)->postJson($url, hanoiShippingOrderPayload(['quoted_shipping_fee' => 25000]))->assertConflict();

    $this->assertDatabaseHas('shop_orders', ['id' => $first->json('order.id'), 'shipping_fee' => 20000, 'shipping_province_code' => 1, 'shipping_ward_code' => 70]);
    $this->assertDatabaseCount('shop_orders', 1);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
    expect($product->fresh()->stock)->toBe(4);
});

it('returns 422 for a missing or tampered shipping quote without creating an order', function (?int $quotedFee): void {
    $setting = ShopShippingSetting::factory()->create(['enabled' => true, 'base_fee' => 30000]);
    $shop = $setting->shopOwner;
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 5]);
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(hanoiShippingProductUrl($shop, $product).'/orders', hanoiShippingOrderPayload(['quoted_shipping_fee' => $quotedFee]))
        ->assertUnprocessable()->assertJsonValidationErrors('quoted_shipping_fee')
        ->assertJsonPath('errors.quoted_shipping_fee.0', 'Phí vận chuyển đã thay đổi. Vui lòng kiểm tra phí mới trước khi đặt hàng.');
    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(5);
})->with(['missing quote' => null, 'fake free shipping' => 0, 'old quote' => 25000]);

it('preserves checkout totals for unconfigured shops and destinations outside Hanoi', function (bool $enabled, int $provinceCode, int $wardCode): void {
    $setting = ShopShippingSetting::factory()->create(['enabled' => $enabled, 'base_fee' => 30000]);
    $shop = $setting->shopOwner;
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 100000, 'stock' => 5]);
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(hanoiShippingProductUrl($shop, $product).'/orders', hanoiShippingOrderPayload([
            'shipping_province_code' => $provinceCode, 'shipping_ward_code' => $wardCode,
        ]))->assertCreated()->assertJsonPath('order.shipping_fee', 0)->assertJsonPath('order.total', 100000);
    expect(ShopOrder::query()->firstOrFail()->shipping_total)->toBe(0);
})->with(['disabled shipping' => [false, 1, 70], 'outside Hanoi' => [true, 4, 1273]]);

it('accepts an explicitly configured free ward with an acknowledged zero quote', function (): void {
    $setting = ShopShippingSetting::factory()->create(['enabled' => true, 'base_fee' => 30000, 'ward_rates' => [['ward_code' => 70, 'fee' => 0]]]);
    $shop = $setting->shopOwner;
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 100000, 'stock' => 5]);
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(hanoiShippingProductUrl($shop, $product).'/orders', hanoiShippingOrderPayload(['quoted_shipping_fee' => 0]))
        ->assertCreated()->assertJsonPath('order.shipping_fee', 0)->assertJsonPath('order.total', 100000);
    $this->assertDatabaseHas('shop_orders', ['shop_owner_id' => $shop->id, 'shipping_fee' => 0, 'shipping_total' => 0, 'total' => 100000]);
});

it('returns 422 before reservation when shipping pushes the total over storage bounds', function (): void {
    $setting = ShopShippingSetting::factory()->create(['enabled' => true, 'base_fee' => 1]);
    $shop = $setting->shopOwner;
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['price' => 858993459, 'stock' => 5]);
    Sanctum::actingAs(User::factory()->customer()->create());

    $this->withHeader('Idempotency-Key', (string) Str::uuid())
        ->postJson(hanoiShippingProductUrl($shop, $product).'/orders', hanoiShippingOrderPayload(['quantity' => 5, 'quoted_shipping_fee' => 1]))
        ->assertUnprocessable()->assertJsonValidationErrors('quantity');
    $this->assertDatabaseCount('shop_orders', 0);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    expect($product->fresh()->stock)->toBe(5);
});
