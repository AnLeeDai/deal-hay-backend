<?php

use App\Actions\SaveShopProductDetails;
use App\Actions\SyncShopProductInventory;
use App\Actions\UpdateShopOrderStatus;
use App\Models\ProductCategory;
use App\Models\ShopInventoryMovement;
use App\Models\ShopInventoryUnit;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use App\Services\MediaClient;
use App\Services\ShopInventoryUnitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

it('backfills exactly one persistent globally unique SKU and QR per physical unit', function (): void {
    $first = ShopProduct::factory()->create(['stock' => 2]);
    $second = ShopProduct::factory()->create(['stock' => 2]);
    $service = app(ShopInventoryUnitService::class);
    $original = $service->ensureForProduct($first);
    $others = $service->ensureForProduct($second);

    expect($original)->toHaveCount(2);
    expect($original->pluck('sku')->unique())->toHaveCount(2);
    expect($original->map(fn ($unit) => $unit->code)->unique())->toHaveCount(2);
    expect($original->first()->code)->toBe($original->first()->sku);
    expect($original->first()->sku)->toBe('DHU-'.strtoupper($original->first()->getKey()));
    expect($original->pluck('sku')->intersect($others->pluck('sku')))->toBeEmpty();

    $first->update(['name' => 'A new product name', 'sku' => 'CHANGED-SKU', 'price' => 123456]);
    expect($service->ensureForProduct($first)->modelKeys())->toBe($original->modelKeys());
    $this->assertDatabaseCount('shop_inventory_units', 4);
});

it('removes and reinstates the exact selected physical units without replacing existing labels', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 3]);
    $units = app(ShopInventoryUnitService::class)->ensureForProduct($product);
    $selected = $units->last()->getKey();
    $inventory = app(SyncShopProductInventory::class);

    $inventory->adjust($product, null, -1, 'stocktake', unitIds: [$selected]);
    expect($product->refresh()->stock)->toBe(2);
    expect(ShopInventoryUnit::findOrFail($selected)->status)->toBe('removed');
    expect(app(ShopInventoryUnitService::class)->availableForProduct($product)->pluck('id')->all())->toBe($units->take(2)->modelKeys());

    $inventory->adjust($product, null, 1, 'stocktake', unitIds: [$selected]);
    expect($product->refresh()->stock)->toBe(3);
    expect(app(ShopInventoryUnitService::class)->ensureForProduct($product)->modelKeys())->toBe($units->modelKeys());
    $this->assertDatabaseCount('shop_inventory_units', 3);
});

it('issues new labels for ordinary receipts instead of reusing removed identities', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    $original = app(ShopInventoryUnitService::class)->ensureForProduct($product);
    $inventory = app(SyncShopProductInventory::class);
    $inventory->adjust($product, null, -1, 'manual_adjustment');
    $inventory->adjust($product, null, 1, 'manual_adjustment');

    expect($original->first()->refresh()->status)->toBe('removed');
    $current = app(ShopInventoryUnitService::class)->ensureForProduct($product);
    expect($current)->toHaveCount(2);
    expect($current->modelKeys())->toContain($original->last()->getKey());
    expect($current->modelKeys())->not->toContain($original->first()->getKey());
    $this->assertDatabaseCount('shop_inventory_units', 3);
});

it('rejects cross-product, duplicate and mismatched unit selections atomically', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    $foreign = ShopProduct::factory()->create(['stock' => 1]);
    $units = app(ShopInventoryUnitService::class)->ensureForProduct($product);
    $foreignId = app(ShopInventoryUnitService::class)->ensureForProduct($foreign)->first()->getKey();
    $inventory = app(SyncShopProductInventory::class);
    foreach ([[-1, [$foreignId]], [-2, [$units->first()->getKey(), $units->first()->getKey()]], [-1, []], [1, [$units->first()->getKey()]]] as [$delta, $ids]) {
        expect(fn () => $inventory->adjust($product, null, $delta, 'stocktake', unitIds: $ids))->toThrow(ValidationException::class);
        expect($product->refresh()->stock)->toBe(2);
        expect(app(ShopInventoryUnitService::class)->availableForProduct($product)->count())->toBe(2);
    }
    $this->assertDatabaseCount('shop_inventory_movements', 0);
    $this->assertDatabaseCount('shop_inventory_units', 3);
});

it('uses locked current stock when two callers hold stale product instances', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    $stale = $product->fresh();
    $inventory = app(SyncShopProductInventory::class);
    $inventory->adjust($product, null, 1, 'manual_adjustment');
    $inventory->adjust($stale, null, 1, 'manual_adjustment');

    expect($product->refresh()->stock)->toBe(4);
    expect(app(ShopInventoryUnitService::class)->ensureForProduct($product))->toHaveCount(4);
    expect(ShopInventoryMovement::orderBy('id')->pluck('stock_after')->all())->toBe([3, 4]);
});

it('keeps inactive physical stock available and removes retired variant labels without deleting identities', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 0, 'is_active' => false]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 2, 'is_active' => false]);
    $service = app(ShopInventoryUnitService::class);
    $original = $service->ensureForProduct($product->refresh(), $variant);
    expect($original)->toHaveCount(2);
    $variant->update(['retired_at' => now()]);
    expect($service->ensureForProduct($product, $variant))->toBeEmpty();
    expect(ShopInventoryUnit::whereKey($original->modelKeys())->where('status', 'removed')->count())->toBe(2);
    expect(fn () => app(SyncShopProductInventory::class)->adjust($product, $variant, 1, 'manual_adjustment'))->toThrow(ValidationException::class);
});

it('initializes unit labels through product creation and preserves them through inventory updates', function (): void {
    $owner = User::factory()->shopOwner()->create();
    $category = ProductCategory::factory()->create();
    $image = preparedImage('primary.webp');
    $media = $this->partialMock(MediaClient::class);
    $media->shouldReceive('upload')->once()->andReturn('products/primary.webp');
    $media->shouldReceive('deleteLater')->andReturnNull();
    $media->shouldReceive('discard')->andReturnNull();
    $action = app(SaveShopProductDetails::class);
    $data = ['name' => 'Serialized product', 'category' => $category->name, 'price' => 10000, 'stock' => 2];
    $product = $action->create($owner, $data, $image, []);
    $original = app(ShopInventoryUnitService::class)->availableForProduct($product)->orderBy('id')->get();
    expect($original)->toHaveCount(2);

    $action->update($owner, $product, [...$data, 'stock' => 1], null, [], []);
    expect($original->first()->refresh()->status)->toBe('removed');
    expect($original->last()->refresh()->status)->toBe('available');
    $this->assertDatabaseCount('shop_inventory_units', 2);
});

it('rolls physical unit changes back with a failed aggregate transaction', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    $original = app(ShopInventoryUnitService::class)->ensureForProduct($product)->modelKeys();
    try {
        DB::transaction(function () use ($product): void {
            app(SyncShopProductInventory::class)->adjust($product, null, 2, 'manual_adjustment');
            throw new RuntimeException('Rollback unit change');
        });
    } catch (RuntimeException) {
        // Verify both aggregate stock and generated labels rolled back together.
    }
    expect($product->refresh()->stock)->toBe(2);
    expect(app(ShopInventoryUnitService::class)->ensureForProduct($product)->modelKeys())->toBe($original);
    $this->assertDatabaseCount('shop_inventory_movements', 0);
});

it('retains old unit identifiers when a product is deleted', function (): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    $units = app(ShopInventoryUnitService::class)->ensureForProduct($product);
    $product->delete();
    expect(ShopInventoryUnit::whereKey($units->modelKeys())->whereNull('shop_product_id')->where('status', 'removed')->count())->toBe(2);
    expect(ShopInventoryUnit::whereKey($units->modelKeys())->pluck('sku')->all())->toBe($units->pluck('sku')->all());
});

it('does not reinstate a reserved or sold unit through a stocktake adjustment', function (string $status): void {
    $product = ShopProduct::factory()->create(['stock' => 2]);
    $order = ShopOrder::factory()->create(['shop_owner_id' => $product->shop_owner_id, 'status' => $status]);
    $inventory = app(SyncShopProductInventory::class);
    $units = app(ShopInventoryUnitService::class)->ensureForProduct($product);
    $reserved = $units->first()->getKey();
    $inventory->adjust($product, null, -1, 'order_reserved', reference: $order, unitIds: [$reserved]);

    expect(fn () => $inventory->adjust($product, null, 1, 'stocktake', unitIds: [$reserved]))->toThrow(ValidationException::class);
    expect($product->refresh()->stock)->toBe(1);
    expect(ShopInventoryUnit::findOrFail($reserved)->status)->toBe('removed');
    expect(app(ShopInventoryUnitService::class)->availableForProduct($product)->count())->toBe(1);
    $this->assertDatabaseCount('shop_inventory_units', 2);
    $this->assertDatabaseCount('shop_inventory_movements', 1);
})->with(['pending', 'confirmed', 'processing', 'shipping', 'completed']);

it('cancels an order after its variant retires while preserving unavailable physical identities', function (): void {
    $owner = User::factory()->shopOwner()->create();
    $product = ShopProduct::factory()->for($owner, 'shopOwner')->create(['stock' => 3]);
    $variant = ShopProductVariant::factory()->for($product, 'product')->create(['stock' => 3]);
    $units = app(ShopInventoryUnitService::class)->ensureForProduct($product->refresh(), $variant);
    $order = ShopOrder::factory()->for($owner, 'shopOwner')->create();
    $order->items()->create([
        'shop_product_id' => $product->getKey(), 'shop_product_variant_id' => $variant->getKey(),
        'product_name' => $product->name, 'variant_name' => 'Retired variant',
        'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000,
    ]);
    app(SyncShopProductInventory::class)->adjust($product, $variant, -1, 'order_reserved', reference: $order);
    $variant->update(['is_active' => false, 'retired_at' => now()]);
    app(ShopInventoryUnitService::class)->retireForProduct($product, $variant);
    $product->update(['stock' => 0]);

    app(UpdateShopOrderStatus::class)->handle($owner, $order, 'cancelled');

    expect($order->refresh()->status)->toBe('cancelled');
    expect($variant->refresh()->stock)->toBe(3);
    expect($product->refresh()->stock)->toBe(0);
    expect(ShopInventoryUnit::orderBy('id')->pluck('id')->all())->toBe($units->modelKeys());
    expect(ShopInventoryUnit::where('status', 'available')->count())->toBe(0);
    expect(ShopInventoryUnit::where('removed_reference_id', $order->getKey())->count())->toBe(0);
    expect(app(ShopInventoryUnitService::class)->availableForProduct($product, $variant)->count())->toBe(0);
});
