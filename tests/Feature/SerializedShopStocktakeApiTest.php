<?php

use App\Actions\SyncShopProductInventory;
use App\Actions\UpdateShopOrderStatus;
use App\Models\ShopInventoryUnit;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\ShopStocktake;
use App\Models\ShopStocktakeUnit;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function serializedStocktakePath(?string $id = null): string
{
    return '/api/private/shop/stocktakes'.($id === null ? '' : '/'.$id);
}

function serializedStocktakeUnits(array $data): Collection
{
    return collect($data['items'])->flatMap(fn (array $item): array => $item['units'] ?? [])->values();
}

function serializedStocktakeConfirm(array $data, bool $adjust = true): array
{
    return ['confirmed' => true, 'adjust_stock' => $adjust, 'confirmation_token' => $data['confirmation_token']];
}

it('assigns a distinct stable SKU and QR to every physical stock unit and uses actual variant prices', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $simple = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 3, 'price' => 100000, 'old_price' => 999999]);
    $parent = ShopProduct::factory()->for($shop, 'shopOwner')->hasVariants()->create(['stock' => 3, 'price' => 200000]);
    ShopProductVariant::factory()->for($parent, 'product')->create(['stock' => 2, 'price' => 250000]);
    ShopProductVariant::factory()->for($parent, 'product')->create(['stock' => 1, 'price' => 0]);
    Sanctum::actingAs($shop);

    $first = $this->postJson(serializedStocktakePath(), ['name' => 'Kiểm kê theo tem'])->assertCreated()
        ->assertJsonPath('data.counting_mode', 'serialized')->assertJsonPath('data.summary.expected', 6)
        ->assertJsonPath('data.summary.expected_value', 800000)->json('data');
    $units = serializedStocktakeUnits($first);
    expect($units)->toHaveCount(6);
    expect($units->pluck('sku')->unique())->toHaveCount(6);
    expect($units->pluck('code')->unique())->toHaveCount(6);
    foreach ($units as $unit) {
        expect($unit['code'])->toBe($unit['sku']);
        expect($unit['expected'])->toBeTrue();
        expect($unit['counted'])->toBeFalse();
    }

    $second = $this->postJson(serializedStocktakePath(), ['name' => 'Kiểm kê lại'])->assertCreated()->json('data');
    expect(serializedStocktakeUnits($second)->pluck('sku')->sort()->values()->all())
        ->toBe($units->pluck('sku')->sort()->values()->all());
    expect($simple->fresh()->stock)->toBe(3);
});

it('counts the same physical QR once even under independent scan IDs or SKU aliases', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 3, 'price' => 100000, 'old_price' => null]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Chống đếm trùng'])->assertCreated()->json('data');
    $units = serializedStocktakeUnits($session);
    $unit = $units[0];
    $path = serializedStocktakePath($session['id']);
    $scan = ['code' => $unit['code'], 'scan_id' => (string) Str::uuid()];
    $this->postJson($path.'/scan', $scan)->assertOk()->assertJsonPath('data.summary.counted', 1);
    $this->postJson($path.'/scan', $scan)->assertOk()->assertJsonPath('data.summary.counted', 1);
    $this->postJson($path.'/scan', ['code' => $unit['code'], 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.counted', 1);
    $this->postJson($path.'/scan', ['code' => strtolower($unit['sku']), 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.counted', 1);
    $this->postJson($path.'/scan', [...$scan, 'code' => $units[1]['code']])->assertUnprocessable();
    $this->postJson($path.'/scan', ['code' => $units[1]['code'], 'quantity' => 2, 'scan_id' => (string) Str::uuid()])->assertUnprocessable();
    $this->getJson($path)->assertOk()->assertJsonPath('data.summary.counted', 1)->assertJsonPath('data.summary.shortage', 2);
    expect($product->fresh()->stock)->toBe(3);
});

it('accepts SKU QR payloads with scanner suffixes and retries legacy aliases as the same physical scan', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Quét SKU'])->assertCreated()->json('data');
    $units = serializedStocktakeUnits($session);
    $unit = $units[0];
    $path = serializedStocktakePath($session['id']);
    $scanId = (string) Str::uuid();
    $scanned = $this->postJson($path.'/scan', ['code' => "\x1d\t ".strtolower($unit['sku'])."\r\n\x7f", 'scan_id' => $scanId])
        ->assertOk()->assertJsonPath('data.summary.counted', 1)->json('data');
    expect(DB::table('shop_stocktake_scans')->where('scan_id', $scanId)->value('code'))->toBe($unit['sku']);
    $legacy = 'dh:u:'.strtoupper($unit['inventory_unit_id']);
    $this->postJson($path.'/scan', ['code' => "\t{$legacy}\r\n", 'scan_id' => $scanId])->assertOk()
        ->assertJsonPath('data.summary.counted', 1)->assertJsonPath('data.confirmation_token', $scanned['confirmation_token']);
    $this->postJson($path.'/scan', ['code' => $legacy, 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.counted', 1);
    $this->postJson($path.'/scan', ['code' => $units[1]['sku'], 'scan_id' => $scanId])->assertUnprocessable()->assertJsonValidationErrors('scan_id');
    expect(ShopStocktakeUnit::where('shop_stocktake_id', $session['id'])->where('counted', true)->count())->toBe(1);
});

it('rejects combined scans and embedded control characters without recording unknown or counted stock', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Từng tem riêng'])->assertCreated()->json('data');
    $units = serializedStocktakeUnits($session);
    $path = serializedStocktakePath($session['id']);
    foreach (["{$units[0]['sku']}\r\n{$units[1]['sku']}", substr_replace($units[0]['sku'], "\t", 6, 0)] as $code) {
        $this->postJson($path.'/scan', ['code' => $code, 'scan_id' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('code');
    }
    $this->getJson($path)->assertOk()->assertJsonPath('data.summary.counted', 0)->assertJsonPath('data.summary.unknown', 0)
        ->assertJsonPath('data.confirmation_token', $session['confirmation_token']);
    expect(DB::table('shop_stocktake_scans')->where('shop_stocktake_id', $session['id'])->count())->toBe(0);
});

it('preserves stored legacy QR snapshots and confirmation tokens when only the canonical label format changes', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 1]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Phiên đã phát hành tem cũ'])->assertCreated()->json('data');
    $unit = serializedStocktakeUnits($session)->first();
    $legacy = 'DH:U:'.$unit['inventory_unit_id'];
    ShopStocktakeUnit::whereKey($unit['id'])->update(['code' => $legacy]);
    $path = serializedStocktakePath($session['id']);
    $legacyReport = $this->getJson($path)->assertOk()->assertJsonPath('data.items.0.units.0.code', $legacy)->json('data');

    // Reconstruct the existing report signature so changing the model accessor
    // cannot silently rewrite old snapshots or invalidate an open confirmation.
    $stocktake = ShopStocktake::findOrFail($session['id']);
    $expectedToken = hash('sha256', json_encode([$stocktake->getKey(), $stocktake->revision, $legacyReport['items'], $legacyReport['financial_summary']], JSON_THROW_ON_ERROR));
    expect($legacyReport['confirmation_token'])->toBe($expectedToken);
    $this->getJson('/api/private/shop/inventory-units')->assertOk()->assertJsonPath('data.0.code', $unit['sku']);
    $this->getJson($path)->assertOk()->assertJsonPath('data.confirmation_token', $expectedToken)->assertJsonPath('data.items.0.units.0.code', $legacy);
    $this->postJson($path.'/complete', serializedStocktakeConfirm($legacyReport, false))->assertOk()->assertJsonPath('data.items.0.units.0.code', $legacy);
});

it('accepts a SKU retry for a previously stored legacy QR scan without changing its confirmation token', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 1]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Lượt quét cũ'])->assertCreated()->json('data');
    $unit = serializedStocktakeUnits($session)->first();
    $path = serializedStocktakePath($session['id']);
    $scanId = (string) Str::uuid();
    $first = $this->postJson($path.'/scan', ['code' => $unit['sku'], 'scan_id' => $scanId])->assertOk()->json('data');
    DB::table('shop_stocktake_scans')->where('scan_id', $scanId)->update(['code' => 'DH:U:'.$unit['inventory_unit_id']]);
    $this->postJson($path.'/scan', ['code' => strtolower($unit['sku'])."\r\n\t", 'scan_id' => $scanId])->assertOk()
        ->assertJsonPath('data.summary.counted', 1)->assertJsonPath('data.confirmation_token', $first['confirmation_token']);
    expect(DB::table('shop_stocktake_scans')->where('shop_stocktake_id', $session['id'])->count())->toBe(1);
});

it('rejects aggregate product codes and manual count changes and permits an explicit undo followed by rescan', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Chỉ quét từng tem'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    $unit = serializedStocktakeUnits($session)->first();
    foreach (["DH:P:{$product->id}", "DH:V:{$product->id}", $product->sku] as $code) {
        $this->postJson($path.'/scan', ['code' => $code, 'scan_id' => (string) Str::uuid()])->assertUnprocessable();
    }
    $this->patchJson($path.'/items/'.$session['items'][0]['id'], ['counted_quantity' => 2])->assertUnprocessable();
    $this->postJson($path.'/scan', ['code' => $unit['code'], 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.counted', 1);
    $this->patchJson($path.'/units/'.$unit['id'], ['counted' => true])->assertUnprocessable();
    $this->patchJson($path.'/units/'.$unit['id'], ['counted' => false])->assertOk()->assertJsonPath('data.summary.counted', 0);
    $this->postJson($path.'/scan', ['code' => $unit['sku'], 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.counted', 1);
});

it('saves a draft and freezes confirmed monetary totals and exact counted labels across later repricing', function (): void {
    $this->freezeTime();
    $product = ShopProduct::factory()->create(['stock' => 3, 'price' => 100000, 'old_price' => 900000]);
    $shop = $product->shopOwner;
    Sanctum::actingAs($shop);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Công nợ cuối ngày'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    $units = serializedStocktakeUnits($session);
    $this->postJson($path.'/scan', ['code' => $units[0]['code'], 'scan_id' => (string) Str::uuid()])->assertOk();
    $saved = $this->postJson($path.'/save')->assertOk()->assertJsonPath('data.status', 'counting')
        ->assertJsonPath('data.summary.expected_value', 300000)->assertJsonPath('data.summary.counted_value', 100000)
        ->assertJsonPath('data.summary.shortage_value', 200000)->assertJsonPath('data.summary.debt_amount', 200000)
        ->assertJsonPath('data.summary.net_difference_value', -200000)->json('data');
    expect($saved['saved_at'])->not->toBeNull();
    expect($product->fresh()->stock)->toBe(3);
    $this->getJson($path)->assertOk()->assertJsonPath('data.summary.counted', 1);

    $confirmed = $this->postJson($path.'/complete', serializedStocktakeConfirm($saved))->assertOk()
        ->assertJsonPath('data.status', 'completed')->assertJsonPath('data.stock_adjusted', true)
        ->assertJsonPath('data.confirmed_by', $shop->id)->assertJsonPath('data.summary.debt_amount', 200000)->json('data');
    expect($confirmed['confirmed_at'])->not->toBeNull();
    expect($product->fresh()->stock)->toBe(1);
    expect(ShopInventoryUnit::query()->where('shop_product_id', $product->id)->where('status', 'available')->pluck('id')->all())
        ->toBe([$units[0]['inventory_unit_id']]);
    $this->postJson($path.'/complete', serializedStocktakeConfirm($saved))->assertOk()->assertJsonPath('data.summary.debt_amount', 200000);
    expect($product->fresh()->stock)->toBe(1);

    $product->update(['price' => 990000, 'name' => 'Tên sau khi kiểm kê']);
    $this->getJson($path)->assertOk()->assertJsonPath('data.summary.expected_value', 300000)
        ->assertJsonPath('data.summary.counted_value', 100000)->assertJsonPath('data.summary.debt_amount', 200000)
        ->assertJsonPath('data.confirmation_token', $confirmed['confirmation_token']);
    $product->delete();
    $this->getJson($path)->assertOk()->assertJsonPath('data.summary.expected_value', 300000)->assertJsonPath('data.summary.debt_amount', 200000);
    $this->postJson($path.'/scan', ['code' => $units[1]['code'], 'scan_id' => (string) Str::uuid()])->assertUnprocessable();
    $this->patchJson($path.'/units/'.$units[0]['id'], ['counted' => false])->assertUnprocessable();
    $this->postJson($path.'/complete', serializedStocktakeConfirm($confirmed))->assertUnprocessable();
});

it('requires a fresh explicit confirmation after price or scan state changes', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2, 'price' => 100000, 'old_price' => null]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Xác nhận tiền'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    $unit = serializedStocktakeUnits($session)->first();
    $this->postJson($path.'/complete', ['adjust_stock' => true])->assertUnprocessable();
    $this->postJson($path.'/complete', [...serializedStocktakeConfirm($session), 'confirmed' => false])->assertUnprocessable();
    $product->update(['price' => 200000]);
    $this->postJson($path.'/complete', serializedStocktakeConfirm($session))->assertUnprocessable();
    $refreshed = $this->postJson($path.'/save')->assertOk()->assertJsonPath('data.summary.expected_value', 400000)->json('data');
    expect($refreshed['confirmation_token'])->not->toBe($session['confirmation_token']);
    $this->postJson($path.'/scan', ['code' => $unit['code'], 'scan_id' => (string) Str::uuid()])->assertOk();
    $this->postJson($path.'/complete', serializedStocktakeConfirm($refreshed))->assertUnprocessable();
    $latest = $this->postJson($path.'/save')->assertOk()->assertJsonPath('data.summary.debt_amount', 200000)->json('data');
    $this->postJson($path.'/complete', serializedStocktakeConfirm($latest, false))->assertOk()->assertJsonPath('data.stock_adjusted', false);
    expect($product->fresh()->stock)->toBe(2);
});

it('keeps unknown and another shops physical codes outside monetary liability and does not disclose foreign products', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 1, 'price' => 100000]);
    $other = ShopProduct::factory()->create(['stock' => 1, 'price' => 999999]);
    Sanctum::actingAs($other->shopOwner);
    $otherSession = $this->postJson(serializedStocktakePath(), ['name' => 'Kho riêng'])->assertCreated()->json('data');
    $foreignCode = serializedStocktakeUnits($otherSession)->first()['code'];
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Mã ngoài kho'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    $this->postJson($path.'/scan', ['code' => $foreignCode, 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.unknown', 1);
    $this->postJson($path.'/scan', ['code' => 'UNKNOWN-QR', 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.unknown', 2);
    $this->postJson($path.'/scan', ['code' => 'UNKNOWN-QR', 'scan_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.summary.unknown', 2);
    $saved = $this->postJson($path.'/save')->assertOk()->assertJsonPath('data.summary.shortage_value', 100000)
        ->assertJsonPath('data.summary.surplus_value', 0)->assertJsonPath('data.summary.debt_amount', 100000)
        ->assertJsonMissing(['product_name' => $other->name])->json('data');
    $this->postJson($path.'/complete', serializedStocktakeConfirm($saved, false))->assertOk()->assertJsonPath('data.summary.unknown', 2);
    expect($other->fresh()->stock)->toBe(1);
});

it('recognizes an owned removed label as a surplus and reinstates that exact physical label', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2, 'price' => 100000, 'old_price' => null]);
    $shop = $product->shopOwner;
    Sanctum::actingAs($shop);
    $initial = $this->postJson(serializedStocktakePath(), ['name' => 'Tem đã có'])->assertCreated()->json('data');
    $originalUnits = serializedStocktakeUnits($initial);
    $removedUnitId = $originalUnits[0]['inventory_unit_id'];
    app(SyncShopProductInventory::class)->adjust($product, null, -1, 'manual', $shop, null, 'Lấy một đơn vị ra kho', [$removedUnitId]);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Tìm thấy tem cũ'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    $this->postJson($path.'/scan', ['code' => $originalUnits[0]['code'], 'scan_id' => (string) Str::uuid()])->assertOk()
        ->assertJsonPath('data.summary.surplus', 1)->assertJsonPath('data.summary.surplus_value', 100000);
    $currentUnit = serializedStocktakeUnits($session)->first();
    $scanned = $this->postJson($path.'/scan', ['code' => $currentUnit['code'], 'scan_id' => (string) Str::uuid()])->assertOk()->json('data');
    $this->postJson($path.'/complete', serializedStocktakeConfirm($scanned))->assertOk()
        ->assertJsonPath('data.summary.shortage', 0)->assertJsonPath('data.summary.debt_amount', 0)
        ->assertJsonPath('data.summary.net_difference_value', 100000);
    expect($product->fresh()->stock)->toBe(2);
    expect(ShopInventoryUnit::query()->findOrFail($removedUnitId)->status)->toBe('available');
    expect(ShopInventoryUnit::query()->where('shop_product_id', $product->id)->count())->toBe(2);
});

it('rejects stale stock and intervening net zero movements atomically before reconciling any target', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $first = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 2]);
    $second = ShopProduct::factory()->for($shop, 'shopOwner')->create(['stock' => 2]);
    Sanctum::actingAs($shop);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Đồng bộ nguyên tử'])->assertCreated()->json('data');
    $inventory = app(SyncShopProductInventory::class);
    $inventory->adjust($second, null, -1, 'order_reserved', $shop);
    $inventory->adjust($second, null, 1, 'order_released', $shop);
    $this->postJson(serializedStocktakePath($session['id']).'/complete', serializedStocktakeConfirm($session))->assertUnprocessable();
    expect($first->fresh()->stock)->toBe(2);
    expect($second->fresh()->stock)->toBe(2);
    expect(ShopInventoryUnit::query()->where('shop_product_id', $first->id)->where('status', 'available')->count())->toBe(2);
    $this->getJson(serializedStocktakePath($session['id']))->assertOk()->assertJsonPath('data.status', 'counting');
});

it('reconciles a missing serial and a surplus serial independently even when total quantity is unchanged', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2, 'price' => 100000, 'old_price' => null]);
    $shop = $product->shopOwner;
    Sanctum::actingAs($shop);
    $initial = $this->postJson(serializedStocktakePath(), ['name' => 'Gán tem'])->assertCreated()->json('data');
    $units = serializedStocktakeUnits($initial);
    $found = $units[0];
    $missing = $units[1];
    app(SyncShopProductInventory::class)->adjust($product, null, -1, 'manual', $shop, null, 'Tem ra kho', [$found['inventory_unit_id']]);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Số lượng đủ nhưng sai tem'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    $scanned = $this->postJson($path.'/scan', ['code' => $found['code'], 'scan_id' => (string) Str::uuid()])->assertOk()
        ->assertJsonPath('data.summary.expected', 1)->assertJsonPath('data.summary.counted', 1)
        ->assertJsonPath('data.summary.shortage', 1)->assertJsonPath('data.summary.surplus', 1)
        ->assertJsonPath('data.summary.shortage_value', 100000)->assertJsonPath('data.summary.surplus_value', 100000)
        ->assertJsonPath('data.summary.net_difference_value', 0)->assertJsonPath('data.summary.debt_amount', 100000)->json('data');
    $this->postJson($path.'/complete', serializedStocktakeConfirm($scanned))->assertOk();
    expect($product->fresh()->stock)->toBe(1);
    expect(ShopInventoryUnit::query()->findOrFail($found['inventory_unit_id'])->status)->toBe('available');
    expect(ShopInventoryUnit::query()->findOrFail($missing['inventory_unit_id'])->status)->toBe('removed');
    expect(ShopInventoryUnit::query()->where('shop_product_id', $product->id)->count())->toBe(2);
});

it('invalidates a competing session when the first session reconciles the same units', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    Sanctum::actingAs($product->shopOwner);
    $first = $this->postJson(serializedStocktakePath(), ['name' => 'Phiên A'])->assertCreated()->json('data');
    $second = $this->postJson(serializedStocktakePath(), ['name' => 'Phiên B'])->assertCreated()->json('data');
    $firstUnit = serializedStocktakeUnits($first)->first();
    $first = $this->postJson(serializedStocktakePath($first['id']).'/scan', ['code' => $firstUnit['code'], 'scan_id' => (string) Str::uuid()])->assertOk()->json('data');
    $this->postJson(serializedStocktakePath($first['id']).'/complete', serializedStocktakeConfirm($first))->assertOk();
    $this->postJson(serializedStocktakePath($second['id']).'/complete', serializedStocktakeConfirm($second))->assertUnprocessable();
    expect($product->fresh()->stock)->toBe(1);
});

it('preserves aggregate finalized historical sessions without inventing a valuation', function (): void {
    $shop = User::factory()->shopOwner()->create();
    $session = ShopStocktake::query()->create(['shop_owner_id' => $shop->id, 'name' => 'Biên bản cũ', 'status' => 'completed', 'completed_at' => now(), 'stock_adjusted' => false]);
    $session->items()->create(['code' => 'OLD-SKU', 'sku' => 'OLD-SKU', 'product_name' => 'Sản phẩm cũ', 'expected_quantity' => 5, 'counted_quantity' => 3]);
    Sanctum::actingAs($shop);
    $data = $this->getJson(serializedStocktakePath($session->id))->assertOk()->assertJsonPath('data.summary.shortage', 2)->json('data');
    expect($data['counting_mode'])->not->toBe('serialized');
    expect($data['summary']['debt_amount'] ?? null)->toBeNull();
    expect($data['summary']['shortage_value'] ?? null)->toBeNull();
});

it('keeps sessions and individual scan rows private to the owning shop', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 1]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Phiên riêng'])->assertCreated()->json('data');
    $unit = serializedStocktakeUnits($session)->first();
    $otherSession = $this->postJson(serializedStocktakePath(), ['name' => 'Phiên khác cùng shop'])->assertCreated()->json('data');
    $otherUnit = serializedStocktakeUnits($otherSession)->first();
    $this->patchJson(serializedStocktakePath($session['id']).'/units/'.$otherUnit['id'], ['counted' => false])->assertNotFound();
    $this->postJson(serializedStocktakePath($session['id']).'/complete', serializedStocktakeConfirm($otherSession))->assertUnprocessable();
    Sanctum::actingAs(User::factory()->shopOwner()->create());
    $path = serializedStocktakePath($session['id']);
    $this->getJson($path)->assertNotFound();
    $this->postJson($path.'/save')->assertNotFound();
    $this->patchJson($path.'/units/'.$unit['id'], ['counted' => false])->assertNotFound();
    $this->postJson($path.'/complete', serializedStocktakeConfirm($session))->assertNotFound();
    Sanctum::actingAs(User::factory()->customer()->create());
    $this->postJson(serializedStocktakePath(), ['name' => 'Không phải shop'])->assertForbidden();
});

it('restores the exact printed unit identities when a customer order reservation is cancelled', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 3, 'price' => 100000, 'old_price' => null]);
    $shop = $product->shopOwner;
    Sanctum::actingAs($shop);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Tem trước đặt hàng'])->assertCreated()->json('data');
    $originalIds = serializedStocktakeUnits($session)->pluck('inventory_unit_id')->sort()->values()->all();
    Sanctum::actingAs(User::factory()->customer()->create());
    $key = (string) Str::uuid();
    $path = '/api/shops/'.$shop->user_code.'/products/'.$product->slug.'/orders';
    $payload = ['customer_name' => 'Người mua', 'customer_phone' => '0901234567', 'shipping_address' => '12 Hàng Bài', 'shipping_province_code' => 1, 'shipping_ward_code' => 70, 'quantity' => 2];
    $response = $this->withHeader('Idempotency-Key', $key)->postJson($path, $payload)->assertCreated();
    $this->withHeader('Idempotency-Key', $key)->postJson($path, $payload)->assertCreated()->assertJsonPath('order.id', $response->json('order.id'));
    expect(ShopInventoryUnit::query()->where('shop_product_id', $product->id)->where('status', 'available')->count())->toBe(1);
    app(UpdateShopOrderStatus::class)->handle($shop, ShopOrder::findOrFail($response->json('order.id')), 'cancelled');
    expect($product->fresh()->stock)->toBe(3);
    expect(ShopInventoryUnit::query()->where('shop_product_id', $product->id)->where('status', 'available')->orderBy('id')->pluck('id')->all())->toBe($originalIds);
    expect(ShopInventoryUnit::query()->where('shop_product_id', $product->id)->count())->toBe(3);
});

it('reclassifies earlier unknown aliases when the physical unit target becomes recognized', function (): void {
    $product = ShopProduct::factory()->hasVariants()->create(['stock' => 1, 'price' => 100000]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 1, 'price' => 150000]);
    Sanctum::actingAs($product->shopOwner);
    $initial = $this->postJson(serializedStocktakePath(), ['name' => 'Tem đã phát hành'])->assertCreated()->json('data');
    $unit = serializedStocktakeUnits($initial)->first();
    $variant->update(['retired_at' => now()]);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Khôi phục mã trong phiên'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    foreach (['DH:U:'.$unit['inventory_unit_id'], strtolower($unit['sku'])] as $code) {
        $this->postJson($path.'/scan', ['code' => $code, 'scan_id' => (string) Str::uuid()])->assertOk();
    }
    $this->getJson($path)->assertOk()->assertJsonPath('data.summary.unknown', 2)->assertJsonPath('data.summary.counted', 2)
        ->assertJsonPath('data.summary.counted_value', 0);
    $variant->update(['retired_at' => null]);
    $this->postJson($path.'/scan', ['code' => $unit['code'], 'scan_id' => (string) Str::uuid()])->assertOk()
        ->assertJsonPath('data.summary.unknown', 0)->assertJsonPath('data.summary.counted', 1)
        ->assertJsonPath('data.summary.surplus', 1)->assertJsonPath('data.summary.counted_value', 150000);
});

it('invalidates confirmation when two hidden serials swap counted state without changing totals', function (): void {
    $this->freezeTime();
    $product = ShopProduct::factory()->create(['stock' => 102, 'price' => 100, 'old_price' => null]);
    Sanctum::actingAs($product->shopOwner);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Thay đổi ngoài trang đầu'])->assertCreated()
        ->assertJsonPath('data.summary.expected', 102)->assertJsonCount(100, 'data.items.0.units')
        ->assertJsonPath('data.items.0.units_total', 102)->json('data');
    $path = serializedStocktakePath($session['id']);
    $item = $session['items'][0];
    $hidden = $this->getJson($path.'/items/'.$item['id'].'/units?page=2&per_page=100')->assertOk()->assertJsonCount(2, 'data')->json('data');
    $firstState = $this->postJson($path.'/scan', ['code' => $hidden[0]['code'], 'scan_id' => (string) Str::uuid()])->assertOk()
        ->assertJsonPath('data.summary.counted', 1)->json('data');
    $this->postJson($path.'/scan', ['code' => $hidden[1]['code'], 'scan_id' => (string) Str::uuid()])->assertOk();
    $lastState = $this->patchJson($path.'/units/'.$hidden[0]['id'], ['counted' => false])->assertOk()
        ->assertJsonPath('data.summary.counted', 1)->assertJsonPath('data.summary.debt_amount', 10100)->json('data');
    expect($lastState['items'][0]['units'])->toBe($firstState['items'][0]['units']);
    expect($lastState['summary'])->toBe($firstState['summary']);
    expect($lastState['confirmation_token'])->not->toBe($firstState['confirmation_token']);
    $this->postJson($path.'/complete', serializedStocktakeConfirm($firstState, false))->assertUnprocessable();
    $this->postJson($path.'/complete', serializedStocktakeConfirm($lastState, false))->assertOk()->assertJsonPath('data.summary.debt_amount', 10100);
});

it('paginates every physical entry without leaking rows across items sessions or owners', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 103]);
    $shop = $product->shopOwner;
    Sanctum::actingAs($shop);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Phân trang tem'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    $itemId = $session['items'][0]['id'];
    $first = $this->getJson($path.'/items/'.$itemId.'/units?page=1&per_page=100')->assertOk()->assertJsonCount(100, 'data')
        ->assertJsonPath('meta.total', 103)->assertJsonPath('meta.current_page', 1)->assertJsonPath('meta.last_page', 2)->json('data');
    $second = $this->getJson($path.'/items/'.$itemId.'/units?page=2&per_page=100')->assertOk()->assertJsonCount(3, 'data')->json('data');
    expect(collect([...$first, ...$second])->pluck('inventory_unit_id')->unique())->toHaveCount(103);
    $this->getJson($path.'/items/'.$itemId.'/units?per_page=101')->assertUnprocessable();
    $otherSession = $this->postJson(serializedStocktakePath(), ['name' => 'Phiên khác'])->assertCreated()->json('data');
    $this->getJson($path.'/items/'.$otherSession['items'][0]['id'].'/units')->assertNotFound();
    Sanctum::actingAs(User::factory()->shopOwner()->create());
    $this->getJson($path.'/items/'.$itemId.'/units')->assertNotFound();
});

it('does not treat units reserved or sold by an order as restorable surplus', function (string $status): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    $shop = $product->shopOwner;
    Sanctum::actingAs($shop);
    $issued = $this->postJson(serializedStocktakePath(), ['name' => 'Tem đã phát hành'])->assertCreated()->json('data');
    $unit = serializedStocktakeUnits($issued)->first();
    $order = ShopOrder::factory()->for($shop, 'shopOwner')->create(['status' => $status]);
    app(SyncShopProductInventory::class)->adjust($product, null, -1, 'order_reserved', $shop, $order, 'Giữ đúng tem cho đơn', [$unit['inventory_unit_id']]);
    $session = $this->postJson(serializedStocktakePath(), ['name' => 'Quét nhầm hàng giữ cho đơn'])->assertCreated()->json('data');
    $path = serializedStocktakePath($session['id']);
    foreach ([$unit['code'], $unit['sku']] as $code) {
        $this->postJson($path.'/scan', ['code' => $code, 'scan_id' => (string) Str::uuid()])->assertUnprocessable();
    }
    $this->getJson($path)->assertOk()->assertJsonPath('data.summary.counted', 0)
        ->assertJsonPath('data.summary.unknown', 0)->assertJsonPath('data.summary.surplus', 0);
    expect($product->fresh()->stock)->toBe(1);
    $reserved = ShopInventoryUnit::query()->findOrFail($unit['inventory_unit_id']);
    expect($reserved->status)->toBe('removed');
    expect($reserved->removed_reference_id)->toBe($order->id);
})->with(['pending', 'completed']);
