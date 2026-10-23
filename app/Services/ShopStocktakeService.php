<?php

namespace App\Services;

use App\Actions\SyncShopProductInventory;
use App\Models\ShopInventoryMovement;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\ShopStocktake;
use App\Models\ShopStocktakeItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopStocktakeService
{
    public function __construct(private readonly SyncShopProductInventory $inventory) {}

    public function create(User $owner, string $name): ShopStocktake
    {
        return DB::transaction(function () use ($owner, $name): ShopStocktake {
            $stocktake = ShopStocktake::query()->create(['shop_owner_id' => $owner->getKey(), 'name' => trim($name)]);
            $products = ShopProduct::query()->where('shop_owner_id', $owner->getKey())->orderBy('id')->lockForUpdate()->get();

            foreach ($products as $product) {
                if ($product->has_variants) {
                    foreach ($product->allVariants()->whereNull('retired_at')->orderBy('id')->lockForUpdate()->get() as $variant) {
                        $this->snapshot($stocktake, $product, $variant);
                    }
                } else {
                    $this->snapshot($stocktake, $product);
                }
            }

            return $stocktake;
        }, attempts: 5);
    }

    public function scan(ShopStocktake $stocktake, string $code, int $quantity, string $scanId): void
    {
        $code = trim($code);
        if ($code === '') {
            throw ValidationException::withMessages(['code' => 'Vui lòng quét hoặc nhập mã sản phẩm.']);
        }

        DB::transaction(function () use ($stocktake, $code, $quantity, $scanId): void {
            $locked = $this->counting($stocktake);
            $previous = DB::table('shop_stocktake_scans')->where('shop_stocktake_id', $locked->getKey())->where('scan_id', $scanId)->first();
            if ($previous) {
                if ($previous->code !== $code || (int) $previous->quantity !== $quantity) {
                    throw ValidationException::withMessages(['scan_id' => 'Lượt quét đã được dùng với nội dung khác.']);
                }

                return;
            }

            $item = $locked->items()->where('code', $code)->first();
            // Also accept the human-readable SKU from a handheld scanner.
            if (! $item) {
                $item = $locked->items()->where('sku', mb_strtoupper($code))->where('is_unknown', false)->first();
            }
            if (! $item) {
                $item = $locked->items()->create([
                    'code' => $code,
                    'product_name' => 'Mã không thuộc danh sách kiểm kê',
                    'expected_quantity' => 0,
                    'counted_quantity' => 0,
                    'is_unknown' => true,
                ]);
            }
            $count = $item->counted_quantity + $quantity;
            if ($count > 999999) {
                throw ValidationException::withMessages(['quantity' => 'Số lượng thực đếm tối đa là 999999.']);
            }
            $item->update(['counted_quantity' => $count]);
            DB::table('shop_stocktake_scans')->insert([
                'shop_stocktake_id' => $locked->getKey(), 'scan_id' => $scanId, 'code' => $code, 'quantity' => $quantity,
            ]);
        }, attempts: 5);
    }

    public function updateItem(ShopStocktake $stocktake, string $itemId, int $count): void
    {
        DB::transaction(function () use ($stocktake, $itemId, $count): void {
            $this->counting($stocktake)->items()->findOrFail($itemId)->update(['counted_quantity' => $count]);
        }, attempts: 5);
    }

    public function complete(ShopStocktake $stocktake, User $owner, bool $adjustStock): void
    {
        DB::transaction(function () use ($stocktake, $owner, $adjustStock): void {
            $locked = $this->counting($stocktake);
            if ($adjustStock) {
                $items = $locked->items()->where('is_unknown', false)->orderBy('shop_product_id')->orderBy('shop_product_variant_id')->get();
                foreach ($items as $item) {
                    $product = ShopProduct::query()->where('shop_owner_id', $owner->getKey())->lockForUpdate()->find($item->shop_product_id);
                    $wasVariant = str_starts_with($item->code, 'DH:V:');
                    $variant = $wasVariant && $product ? $product->allVariants()->lockForUpdate()->find($item->shop_product_variant_id) : null;
                    $target = $wasVariant ? $variant : $product;
                    if (! $target || ($variant && $variant->retired_at) || (! $wasVariant && $product->has_variants)
                        || $target->stock !== $item->expected_quantity || $this->movementCount($product, $variant) !== $item->expected_movement_count) {
                        throw ValidationException::withMessages([
                            'adjust_stock' => "Tồn kho {$item->product_name} đã thay đổi từ khi mở phiên. Lưu biên bản và mở phiên mới để kiểm tra lại trước khi cập nhật tồn.",
                        ]);
                    }
                    $difference = $item->counted_quantity - $item->expected_quantity;
                    if ($difference !== 0) {
                        $this->inventory->adjust($product, $variant, $difference, 'stocktake', $owner, $locked, "Kiểm kê: {$locked->name}");
                    }
                }
            }
            $locked->update(['status' => 'completed', 'stock_adjusted' => $adjustStock, 'completed_at' => now()]);
        }, attempts: 5);
    }

    public function payload(ShopStocktake $stocktake): array
    {
        $items = $stocktake->items()->orderBy('is_unknown')->orderBy('product_name')->orderBy('variant_name')->get();

        return [
            'id' => $stocktake->getKey(),
            'name' => $stocktake->name,
            'status' => $stocktake->status,
            'stock_adjusted' => $stocktake->stock_adjusted,
            'created_at' => $stocktake->created_at?->toIso8601String(),
            'completed_at' => $stocktake->completed_at?->toIso8601String(),
            'summary' => [
                'expected' => $items->sum('expected_quantity'),
                'counted' => $items->sum('counted_quantity'),
                'shortage' => $items->where('is_unknown', false)->sum(fn (ShopStocktakeItem $item): int => max(0, $item->expected_quantity - $item->counted_quantity)),
                'surplus' => $items->where('is_unknown', false)->sum(fn (ShopStocktakeItem $item): int => max(0, $item->counted_quantity - $item->expected_quantity)),
                'unknown' => $items->where('is_unknown', true)->sum('counted_quantity'),
            ],
            'items' => $items->map(fn (ShopStocktakeItem $item): array => [
                'id' => $item->getKey(), 'code' => $item->code, 'sku' => $item->sku,
                'product_name' => $item->product_name, 'variant_name' => $item->variant_name,
                'expected_quantity' => $item->expected_quantity, 'counted_quantity' => $item->counted_quantity,
                'difference' => $item->counted_quantity - $item->expected_quantity,
                'is_unknown' => $item->is_unknown,
                'result' => $item->is_unknown ? 'unknown' : ($item->counted_quantity === $item->expected_quantity ? 'matched' : ($item->counted_quantity > $item->expected_quantity ? 'surplus' : 'shortage')),
            ])->all(),
        ];
    }

    private function counting(ShopStocktake $stocktake): ShopStocktake
    {
        $locked = ShopStocktake::query()->lockForUpdate()->findOrFail($stocktake->getKey());
        if ($locked->status !== 'counting') {
            throw ValidationException::withMessages(['stocktake' => 'Phiên kiểm kê đã chốt, không thể thay đổi số lượng.']);
        }

        return $locked;
    }

    private function snapshot(ShopStocktake $stocktake, ShopProduct $product, ?ShopProductVariant $variant = null): void
    {
        $stocktake->items()->create([
            'shop_product_id' => $product->getKey(), 'shop_product_variant_id' => $variant?->getKey(),
            'code' => $variant ? "DH:V:{$variant->getKey()}" : "DH:P:{$product->getKey()}",
            'product_name' => $product->name, 'variant_name' => $variant?->displayName(),
            'sku' => $variant?->sku ?? $product->sku, 'expected_quantity' => ($variant ?? $product)->stock,
            'expected_movement_count' => $this->movementCount($product, $variant),
        ]);
    }

    private function movementCount(ShopProduct $product, ?ShopProductVariant $variant): int
    {
        return ShopInventoryMovement::query()->where('shop_product_id', $product->getKey())
            ->where('shop_product_variant_id', $variant?->getKey())
            ->count();
    }
}
