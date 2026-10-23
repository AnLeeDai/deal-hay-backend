<?php

namespace App\Services;

use App\Actions\SyncShopProductInventory;
use App\Models\ShopInventoryMovement;
use App\Models\ShopInventoryUnit;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\ShopStocktake;
use App\Models\ShopStocktakeItem;
use App\Models\ShopStocktakeUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SerializedStocktakeService
{
    public function __construct(
        private readonly ShopInventoryUnitService $units,
        private readonly SyncShopProductInventory $inventory,
    ) {}

    public function snapshot(ShopStocktake $stocktake, ShopProduct $product, ?ShopProductVariant $variant): void
    {
        $this->units->synchronizeForProduct($product, $variant);
        $item = $stocktake->items()->create([
            'shop_product_id' => $product->getKey(), 'shop_product_variant_id' => $variant?->getKey(),
            'code' => $variant ? "DH:V:{$variant->getKey()}" : "DH:P:{$product->getKey()}",
            'sku' => $variant?->sku ?? $product->sku, 'product_name' => $product->name,
            'variant_name' => $variant?->displayName(), 'expected_quantity' => $this->units->availableForProduct($product, $variant)->count(),
            'expected_movement_count' => $this->movementCount($product, $variant),
            'unit_price' => $variant?->price ?? $product->price,
        ]);
        $this->units->availableForProduct($product, $variant)->orderBy('id')->chunkById(500, function ($units) use ($stocktake, $item): void {
            $timestamp = now();
            ShopStocktakeUnit::query()->insert($units->map(fn (ShopInventoryUnit $unit): array => [
                'id' => (string) Str::uuid7(), 'shop_stocktake_id' => $stocktake->getKey(), 'shop_stocktake_item_id' => $item->getKey(),
                'inventory_unit_id' => $unit->getKey(), 'sku' => $unit->sku, 'code' => $unit->code,
                'expected' => true, 'counted' => false, 'created_at' => $timestamp, 'updated_at' => $timestamp,
            ])->all());
        });
    }

    public function scan(ShopStocktake $stocktake, string $code, int $quantity, string $scanId): void
    {
        $code = $this->normalizeCode($code);
        if ($quantity !== 1 || $code === '') {
            throw ValidationException::withMessages(['quantity' => 'Mỗi tem định danh một đơn vị hàng. Mỗi lượt quét chỉ được ghi nhận 1 đơn vị.']);
        }
        DB::transaction(function () use ($stocktake, $code, $scanId): void {
            $locked = $this->counting($stocktake);
            $unit = $this->findUnit((string) $locked->shop_owner_id, $code);
            $previous = DB::table('shop_stocktake_scans')->where('shop_stocktake_id', $locked->getKey())->where('scan_id', $scanId)->first();
            if ($previous) {
                $previousCode = $this->normalizeCode($previous->code);
                $previousUnit = $previousCode === $code ? $unit : $this->findUnit((string) $locked->shop_owner_id, $previousCode);
                if (($previousCode !== $code && (! $unit || ! $previousUnit || ! $unit->is($previousUnit))) || (int) $previous->quantity !== 1) {
                    throw ValidationException::withMessages(['scan_id' => 'Lượt quét đã được dùng với nội dung khác.']);
                }

                return;
            }
            if (preg_match('/^DH:[PV]:/i', $code)) {
                throw ValidationException::withMessages(['code' => 'Đây là mã chung của mặt hàng. Vui lòng in và quét tem SKU riêng của từng đơn vị hàng.']);
            }

            if (! $unit && (ShopProduct::query()->where('shop_owner_id', $locked->shop_owner_id)->where('sku_normalized', Str::upper($code))->exists()
                || ShopProductVariant::query()->where('shop_owner_id', $locked->shop_owner_id)->where('sku_normalized', Str::upper($code))->exists())) {
                throw ValidationException::withMessages(['code' => 'Đây là mã chung của mặt hàng. Vui lòng in và quét tem SKU riêng của từng đơn vị hàng.']);
            }
            $entry = $unit ? ShopStocktakeUnit::query()->where('shop_stocktake_id', $locked->getKey())->where('inventory_unit_id', $unit->getKey())->first() : null;
            if ($entry) {
                $entry->update(['counted' => true]);
                $this->recount($entry->shop_stocktake_item_id);
            } elseif ($unit && $this->validUnitTarget($unit)) {
                if ($unit->status === 'removed' && $unit->removed_reference_type === (new ShopOrder)->getMorphClass()
                    && ShopOrder::query()->whereKey($unit->removed_reference_id)->where('status', '!=', 'cancelled')->exists()) {
                    throw ValidationException::withMessages(['code' => 'Tem này thuộc hàng đã giữ cho một đơn hàng. Không thể ghi nhận là hàng thừa để đưa lại vào tồn khả dụng.']);
                }
                $item = $locked->items()->where('shop_product_id', $unit->shop_product_id)->where('shop_product_variant_id', $unit->shop_product_variant_id)->where('is_unknown', false)->first();
                if (! $item) {
                    $product = ShopProduct::query()->findOrFail($unit->shop_product_id);
                    $variant = $unit->shop_product_variant_id ? ShopProductVariant::query()->find($unit->shop_product_variant_id) : null;
                    $item = $locked->items()->create([
                        'shop_product_id' => $product->getKey(), 'shop_product_variant_id' => $variant?->getKey(),
                        'code' => $variant ? "DH:V:{$variant->getKey()}" : "DH:P:{$product->getKey()}",
                        'sku' => $variant?->sku ?? $product->sku, 'product_name' => $product->name,
                        'variant_name' => $variant?->displayName(), 'expected_quantity' => 0,
                        'expected_movement_count' => $this->movementCount($product, $variant),
                        'unit_price' => $variant?->price ?? $product->price,
                    ]);
                }
                $entry = $this->addUnit($locked, $item, $unit, false);
                $entry->update(['counted' => true]);
                $this->recount($item->getKey());
            } else {
                $locked->items()->firstOrCreate(['code' => $code, 'is_unknown' => true], [
                    'product_name' => 'Mã không thuộc kho', 'expected_quantity' => 0,
                    'counted_quantity' => 1, 'unit_price' => null,
                ]);
            }
            if ($entry) {
                $locked->items()->where('is_unknown', true)
                    ->whereRaw('LOWER(code) IN (?, ?, ?, ?)', [Str::lower($entry->code), Str::lower($entry->sku), Str::lower($code), 'dh:u:'.Str::lower($entry->inventory_unit_id)])->delete();
            }
            $locked->increment('revision');
            DB::table('shop_stocktake_scans')->insert([
                'shop_stocktake_id' => $locked->getKey(), 'scan_id' => $scanId, 'code' => $unit?->sku ?? $code, 'quantity' => 1,
            ]);
        }, attempts: 5);
    }

    public function undo(ShopStocktake $stocktake, string $entryId): void
    {
        DB::transaction(function () use ($stocktake, $entryId): void {
            $locked = $this->counting($stocktake);
            $entry = ShopStocktakeUnit::query()->where('shop_stocktake_id', $locked->getKey())->findOrFail($entryId);
            $entry->update(['counted' => false]);
            $this->recount($entry->shop_stocktake_item_id);
            $locked->increment('revision');
        }, attempts: 5);
    }

    public function save(ShopStocktake $stocktake): void
    {
        DB::transaction(function () use ($stocktake): void {
            $locked = $this->counting($stocktake);
            $data = $this->report($locked);
            foreach ($data['items'] as $item) {
                $locked->items()->whereKey($item['id'])->update(['unit_price' => $item['unit_price']]);
            }
            $locked->update(['saved_at' => now()]);
        }, attempts: 5);
    }

    public function complete(ShopStocktake $stocktake, User $owner, bool $adjustStock, string $token): void
    {
        DB::transaction(function () use ($stocktake, $owner, $adjustStock, $token): void {
            $locked = ShopStocktake::query()->lockForUpdate()->findOrFail($stocktake->getKey());
            if ($locked->status === 'completed' && $locked->confirmed_by === $owner->getKey()
                && $locked->stock_adjusted === $adjustStock && hash_equals((string) $locked->confirmation_hash, $token)) {
                return;
            }
            if ($locked->status !== 'counting') {
                throw ValidationException::withMessages(['stocktake' => 'Phiên kiểm kê đã chốt.']);
            }
            $items = $locked->items()->where('is_unknown', false)->with('units')->orderBy('shop_product_id')->orderBy('shop_product_variant_id')->get();
            $targets = [];
            foreach ($items as $item) {
                $product = ShopProduct::query()->where('shop_owner_id', $owner->getKey())->lockForUpdate()->find($item->shop_product_id);
                $wasVariant = str_starts_with($item->code, 'DH:V:');
                $variant = $wasVariant && $product ? $product->allVariants()->lockForUpdate()->find($item->shop_product_variant_id) : null;
                $target = $wasVariant ? $variant : $product;
                if ($adjustStock) {
                    if (! $target || ($variant && $variant->retired_at) || (! $wasVariant && $product->has_variants)
                        || $target->stock !== $item->expected_quantity || $this->movementCount($product, $variant) !== $item->expected_movement_count) {
                        $this->staleStock($item);
                    }
                    $availableIds = $this->units->availableForProduct($product, $variant)->lockForUpdate()->pluck('id')->sort()->values()->all();
                    $expectedIds = $item->units->where('expected', true)->pluck('inventory_unit_id')->sort()->values()->all();
                    if ($availableIds !== $expectedIds) {
                        $this->staleStock($item);
                    }
                    $targets[$item->getKey()] = [$product, $variant];
                }
            }
            $data = $this->report($locked);
            if (! hash_equals((string) $data['confirmation_token'], $token)) {
                throw ValidationException::withMessages(['confirmation_token' => 'Số lượng hoặc giá bán đã thay đổi. Tải lại phiên và kiểm tra công nợ trước khi xác nhận.']);
            }
            foreach ($data['items'] as $item) {
                $locked->items()->whereKey($item['id'])->update(['unit_price' => $item['unit_price']]);
            }
            if ($adjustStock) {
                foreach ($items as $item) {
                    [$product, $variant] = $targets[$item->getKey()];
                    $missing = $item->units->where('expected', true)->where('counted', false)->pluck('inventory_unit_id')->all();
                    $found = $item->units->where('expected', false)->where('counted', true)->pluck('inventory_unit_id')->all();
                    if ($missing !== []) {
                        $this->inventory->adjust($product, $variant, -count($missing), 'stocktake', $owner, $locked, "Thiếu hàng: {$locked->name}", unitIds: $missing);
                    }
                    if ($found !== []) {
                        $this->inventory->adjust($product, $variant, count($found), 'stocktake', $owner, $locked, "Thừa hàng: {$locked->name}", unitIds: $found);
                    }
                }
            }
            $locked->update([
                'status' => 'completed', 'stock_adjusted' => $adjustStock, 'saved_at' => now(),
                'completed_at' => now(), 'confirmed_at' => now(), 'confirmed_by' => $owner->getKey(),
                'confirmation_hash' => $token, 'financial_summary' => $data['financial_summary'],
            ]);
        }, attempts: 5);
    }

    public function report(ShopStocktake $stocktake): array
    {
        $items = $stocktake->items()->with(['units' => fn ($query) => $query->orderBy('sku')->limit(100)])
            ->withCount([
                'units',
                'units as expected_total' => fn ($query) => $query->where('expected', true),
                'units as counted_total' => fn ($query) => $query->where('counted', true),
                'units as missing_total' => fn ($query) => $query->where('expected', true)->where('counted', false),
                'units as surplus_total' => fn ($query) => $query->where('expected', false)->where('counted', true),
            ])->orderBy('is_unknown')->orderBy('product_name')->orderBy('variant_name')->orderBy('id')->get();
        $products = ShopProduct::query()->where('shop_owner_id', $stocktake->shop_owner_id)->whereIn('id', $items->pluck('shop_product_id')->filter())->get()->keyBy('id');
        $variants = ShopProductVariant::query()->where('shop_owner_id', $stocktake->shop_owner_id)->whereIn('id', $items->pluck('shop_product_variant_id')->filter())->get()->keyBy('id');
        $financial = ['expected_value' => 0, 'counted_value' => 0, 'shortage_value' => 0, 'surplus_value' => 0, 'net_difference_value' => 0, 'debt_amount' => 0];
        $rows = $items->map(function (ShopStocktakeItem $item) use ($stocktake, $products, $variants, &$financial): array {
            $price = $item->is_unknown ? null : ($stocktake->status === 'completed'
                ? $item->unit_price : ($variants->get($item->shop_product_variant_id)?->price ?? $products->get($item->shop_product_id)?->price ?? $item->unit_price));
            $expected = $item->is_unknown ? 0 : (int) $item->expected_total;
            $counted = $item->is_unknown ? $item->counted_quantity : (int) $item->counted_total;
            // Serial identity matters: one missing serial and one unexpected serial remain separate discrepancies.
            $missing = $item->is_unknown ? 0 : (int) $item->missing_total;
            $surplus = $item->is_unknown ? 0 : (int) $item->surplus_total;
            $money = $price === null ? null : [
                'expected_value' => $expected * $price, 'counted_value' => $counted * $price,
                'shortage_value' => $missing * $price, 'surplus_value' => $surplus * $price,
            ];
            foreach ($money ?? [] as $key => $amount) {
                $financial[$key] += $amount;
            }

            return [
                'id' => $item->getKey(), 'code' => $item->code, 'sku' => $item->sku,
                'product_name' => $item->product_name, 'variant_name' => $item->variant_name,
                'expected_quantity' => $expected, 'counted_quantity' => $counted,
                'shortage_quantity' => $missing, 'surplus_quantity' => $surplus,
                'difference' => $counted - $expected, 'is_unknown' => $item->is_unknown,
                'result' => $item->is_unknown ? 'unknown' : ($missing > 0 ? 'shortage' : ($surplus > 0 ? 'surplus' : 'matched')),
                'unit_price' => $price, 'values' => $money,
                'units_total' => (int) $item->units_count,
                'units' => $item->units->sortBy('sku')->values()->map(fn (ShopStocktakeUnit $entry): array => [
                    'id' => $entry->getKey(), 'inventory_unit_id' => $entry->inventory_unit_id,
                    'sku' => $entry->sku, 'code' => $entry->code, 'expected' => $entry->expected, 'counted' => $entry->counted,
                ])->all(),
            ];
        })->all();
        $financial['net_difference_value'] = $financial['surplus_value'] - $financial['shortage_value'];
        $financial['debt_amount'] = $financial['shortage_value'];
        if ($stocktake->status === 'completed' && is_array($stocktake->financial_summary)) {
            $financial = $stocktake->financial_summary;
        }
        $token = hash('sha256', json_encode([$stocktake->getKey(), $stocktake->revision, $rows, $financial], JSON_THROW_ON_ERROR));

        return [
            'id' => $stocktake->getKey(), 'name' => $stocktake->name, 'status' => $stocktake->status,
            'counting_mode' => 'serialized', 'stock_adjusted' => $stocktake->stock_adjusted,
            'created_at' => $stocktake->created_at?->toIso8601String(), 'saved_at' => $stocktake->saved_at?->toIso8601String(),
            'completed_at' => $stocktake->completed_at?->toIso8601String(),
            'confirmed_at' => $stocktake->confirmed_at?->toIso8601String(), 'confirmed_by' => $stocktake->confirmed_by,
            'valuation_basis' => 'sale_price', 'financial_summary' => $financial,
            'confirmation_token' => $stocktake->status === 'counting' ? $token : null,
            'summary' => array_merge([
                'expected' => array_sum(array_column($rows, 'expected_quantity')),
                'counted' => array_sum(array_column($rows, 'counted_quantity')),
                'shortage' => array_sum(array_column($rows, 'shortage_quantity')),
                'surplus' => array_sum(array_column($rows, 'surplus_quantity')),
                'unknown' => collect($rows)->where('is_unknown', true)->sum('counted_quantity'),
            ], $financial),
            'items' => $rows,
        ];
    }

    private function validUnitTarget(ShopInventoryUnit $unit): bool
    {
        $product = ShopProduct::query()->where('shop_owner_id', $unit->shop_owner_id)->find($unit->shop_product_id);
        if (! $product) {
            return false;
        }
        if ($unit->shop_product_variant_id) {
            return $product->allVariants()->whereNull('retired_at')->whereKey($unit->shop_product_variant_id)->exists();
        }

        return ! $product->has_variants;
    }

    private function addUnit(ShopStocktake $stocktake, ShopStocktakeItem $item, ShopInventoryUnit $unit, bool $expected): ShopStocktakeUnit
    {
        return $item->units()->create([
            'shop_stocktake_id' => $stocktake->getKey(), 'inventory_unit_id' => $unit->getKey(),
            'sku' => $unit->sku, 'code' => $unit->code, 'expected' => $expected, 'counted' => false,
        ]);
    }

    private function recount(string $itemId): void
    {
        ShopStocktakeItem::query()->whereKey($itemId)->update([
            'counted_quantity' => ShopStocktakeUnit::query()->where('shop_stocktake_item_id', $itemId)->where('counted', true)->count(),
        ]);
    }

    private function normalizeCode(string $code): string
    {
        $code = preg_replace('/\A[\p{Z}\s\x00-\x20\x7f\x{FEFF}]+|[\p{Z}\s\x00-\x20\x7f\x{FEFF}]+\z/u', '', $code) ?? $code;
        if (preg_match('/[\x00-\x1f\x7f]/', $code)) {
            throw ValidationException::withMessages(['code' => 'Mã quét chứa ký tự điều khiển ở giữa. Vui lòng quét riêng từng tem SKU.']);
        }

        return Str::upper($code);
    }

    private function findUnit(string $ownerId, string $code): ?ShopInventoryUnit
    {
        $query = ShopInventoryUnit::query()->where('shop_owner_id', $ownerId);
        if (str_starts_with($code, 'DH:U:')) {
            $id = Str::lower(substr($code, 5));

            return Str::isUuid($id) ? $query->find($id) : null;
        }

        return $query->where('sku', $code)->first();
    }

    private function counting(ShopStocktake $stocktake): ShopStocktake
    {
        $locked = ShopStocktake::query()->lockForUpdate()->findOrFail($stocktake->getKey());
        if ($locked->status !== 'counting') {
            throw ValidationException::withMessages(['stocktake' => 'Phiên kiểm kê đã chốt, không thể thay đổi số lượng.']);
        }

        return $locked;
    }

    private function movementCount(ShopProduct $product, ?ShopProductVariant $variant): int
    {
        return ShopInventoryMovement::query()->where('shop_product_id', $product->getKey())
            ->where('shop_product_variant_id', $variant?->getKey())->count();
    }

    private function staleStock(ShopStocktakeItem $item): never
    {
        throw ValidationException::withMessages(['adjust_stock' => "Tồn kho hoặc danh sách mã của {$item->product_name} đã thay đổi. Lưu phiên và mở phiên mới để đối chiếu lại."]);
    }
}
