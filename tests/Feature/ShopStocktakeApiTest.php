<?php

use App\Actions\SyncShopProductInventory;
use App\Models\ShopInventoryMovement;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\ShopStocktake;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

function stocktakeToken(User $shop): string
{
    return $shop->createToken('stocktake-test')->plainTextToken;
}

// Preserve coverage of sessions created before per-unit tracking was introduced.
function legacyStocktakeResponse(): TestResponse
{
    $response = test()->postJson('/api/private/shop/stocktakes', ['name' => 'Đếm']);
    ShopStocktake::query()->whereKey($response->json('data.id'))->update(['counting_mode' => 'legacy']);

    return $response;
}

it('snapshots hidden products and non retired variants without counting variant parents twice', function (): void {
    $shop = User::factory()->shopOwner()->create();
    ShopProduct::factory()->for($shop, 'shopOwner')->inactive()->create(['stock' => 3]);
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->hasVariants()->create(['stock' => 9]);
    ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 4]);
    ShopProductVariant::factory()->for($product, 'product')->inactive()->create(['stock' => 5]);
    ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 20, 'retired_at' => now()]);
    ShopProduct::factory()->create(['stock' => 99]);

    $this->withToken(stocktakeToken($shop))->postJson('/api/private/shop/stocktakes', ['name' => 'Kiểm kê tháng 10'])
        ->assertCreated()->assertJsonCount(3, 'data.items')
        ->assertJsonPath('data.summary.expected', 12)->assertJsonPath('data.summary.counted', 0)
        ->assertJsonPath('data.summary.shortage', 12);
});

it('counts product QR and SKU scans and reports shortage surplus and unknown stock', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 2, 'sku' => 'TEST-SKU']);
    $this->withToken(stocktakeToken($shop));
    $id = legacyStocktakeResponse()->json('data.id');
    $path = "/api/private/shop/stocktakes/{$id}/scan";
    $this->postJson($path, ['code' => "DH:P:{$product->id}", 'quantity' => 1, 'scan_id' => (string) Str::uuid()])
        ->assertOk()->assertJsonPath('data.items.0.result', 'shortage');
    $this->postJson($path, ['code' => 'test-sku', 'quantity' => 2, 'scan_id' => (string) Str::uuid()])
        ->assertOk()->assertJsonPath('data.items.0.result', 'surplus')->assertJsonPath('data.summary.surplus', 1);
    $this->postJson($path, ['code' => 'UNKNOWN-QR', 'quantity' => 2, 'scan_id' => (string) Str::uuid()])
        ->assertOk()->assertJsonPath('data.items.1.result', 'unknown')->assertJsonPath('data.summary.unknown', 2);
    expect($product->refresh()->stock)->toBe(2);
});

it('deduplicates retried scans but counts independent scans of the same QR', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 2]);
    $this->withToken(stocktakeToken($shop));
    $id = legacyStocktakeResponse()->json('data.id');
    $path = "/api/private/shop/stocktakes/{$id}/scan";
    $payload = ['code' => "DH:P:{$product->id}", 'quantity' => 1, 'scan_id' => (string) Str::uuid()];
    $this->postJson($path, $payload)->assertOk();
    $this->postJson($path, $payload)->assertOk()->assertJsonPath('data.summary.counted', 1);
    $this->postJson($path, [...$payload, 'quantity' => 2])->assertUnprocessable();
    $this->postJson($path, [...$payload, 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.items.0.result', 'matched');
});

it('allows correcting counts to zero and refuses edits after completing a report', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 3]);
    $this->withToken(stocktakeToken($shop));
    $data = legacyStocktakeResponse()->json('data');
    $path = "/api/private/shop/stocktakes/{$data['id']}";
    $item = $data['items'][0]['id'];
    $this->patchJson("{$path}/items/{$item}", ['counted_quantity' => 4])->assertOk();
    $this->patchJson("{$path}/items/{$item}", ['counted_quantity' => 0])->assertOk()->assertJsonPath('data.summary.shortage', 3);
    $this->postJson("{$path}/complete", ['adjust_stock' => false])->assertOk()->assertJsonPath('data.status', 'completed')->assertJsonPath('data.stock_adjusted', false);
    $this->patchJson("{$path}/items/{$item}", ['counted_quantity' => 1])->assertUnprocessable();
    $this->postJson("{$path}/scan", ['code' => "DH:P:{$product->id}", 'scan_id' => (string) Str::uuid()])->assertUnprocessable();
    expect($product->refresh()->stock)->toBe(3);
});

it('reconciles counted inventory and writes an auditable movement without adding unknown products', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 3]);
    $this->withToken(stocktakeToken($shop));
    $data = legacyStocktakeResponse()->json('data');
    $path = "/api/private/shop/stocktakes/{$data['id']}";
    $this->patchJson("{$path}/items/{$data['items'][0]['id']}", ['counted_quantity' => 1])->assertOk();
    $this->postJson("{$path}/scan", ['code' => 'UNKNOWN', 'scan_id' => (string) Str::uuid()])->assertOk();
    $this->postJson("{$path}/complete", ['adjust_stock' => true])->assertOk()->assertJsonPath('data.stock_adjusted', true);
    expect($product->refresh()->stock)->toBe(1);
    $movement = ShopInventoryMovement::query()->sole();
    expect($movement->type)->toBe('stocktake')->and($movement->quantity_delta)->toBe(-2)->and($movement->reference_id)->toBe($data['id']);
    expect(ShopProduct::query()->count())->toBe(1);
});

it('rejects stale inventory instead of overwriting stock changed by an order', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 3]);
    $this->withToken(stocktakeToken($shop));
    $data = legacyStocktakeResponse()->json('data');
    $product->update(['stock' => 2]);
    $path = "/api/private/shop/stocktakes/{$data['id']}";
    $this->postJson("{$path}/complete", ['adjust_stock' => true])->assertUnprocessable()->assertJsonValidationErrors('adjust_stock');
    expect($product->refresh()->stock)->toBe(2);
    $this->getJson($path)->assertOk()->assertJsonPath('data.status', 'counting');
    expect(ShopInventoryMovement::query()->count())->toBe(0);
});

it('reconciles variant stock separately and refreshes the product total', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->hasVariants()->create(['stock' => 5]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 5]);
    $this->withToken(stocktakeToken($shop));
    $data = legacyStocktakeResponse()->json('data');
    $path = "/api/private/shop/stocktakes/{$data['id']}";
    $this->postJson("{$path}/scan", ['code' => "DH:V:{$variant->id}", 'quantity' => 7, 'scan_id' => (string) Str::uuid()])->assertOk();
    $this->postJson("{$path}/complete", ['adjust_stock' => true])->assertOk();
    expect($variant->refresh()->stock)->toBe(7)->and($product->refresh()->stock)->toBe(7);
});

it('detects intervening sale and return even when the final stock equals the snapshot', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 3]);
    $this->withToken(stocktakeToken($shop));
    $data = legacyStocktakeResponse()->json('data');
    $inventory = app(SyncShopProductInventory::class);
    $inventory->adjust($product, null, -1, 'order_reserved', $shop);
    $inventory->adjust($product, null, 1, 'order_cancelled', $shop);
    $this->postJson("/api/private/shop/stocktakes/{$data['id']}/complete", ['adjust_stock' => true])->assertUnprocessable();
    expect($product->refresh()->stock)->toBe(3);
});

it('limits sessions and items to their owning shop and rejects non shop owners', function (): void {
    $shop = User::factory()->shopOwner()->create();
    ShopProduct::factory()->for($shop, 'shopOwner')->create();
    $data = $this->withToken(stocktakeToken($shop))->postJson('/api/private/shop/stocktakes', ['name' => 'Đếm'])->json('data');
    $other = User::factory()->shopOwner()->create();
    $path = "/api/private/shop/stocktakes/{$data['id']}";
    $this->app['auth']->forgetGuards();
    $this->withToken(stocktakeToken($other))->getJson($path)->assertNotFound();
    $this->getJson('/api/private/shop/stocktakes')->assertOk()->assertJsonCount(0, 'data');
    $this->patchJson("{$path}/items/{$data['items'][0]['id']}", ['counted_quantity' => 99])->assertNotFound();
    $this->postJson("{$path}/complete", ['adjust_stock' => true])->assertNotFound();
    $this->app['auth']->forgetGuards();
    $this->withToken(stocktakeToken(User::factory()->create()))->postJson('/api/private/shop/stocktakes', ['name' => 'Đếm'])->assertForbidden();
});

it('rejects deleted inventory and invalid scan quantities', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 3]);
    $this->withToken(stocktakeToken($shop));
    $data = legacyStocktakeResponse()->json('data');
    $path = "/api/private/shop/stocktakes/{$data['id']}";
    $this->postJson("{$path}/scan", ['code' => "DH:P:{$product->id}", 'quantity' => -1, 'scan_id' => (string) Str::uuid()])->assertUnprocessable();
    $product->delete();
    $this->postJson("{$path}/complete", ['adjust_stock' => true])->assertUnprocessable();
});
