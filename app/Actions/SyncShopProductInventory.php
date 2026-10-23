<?php

namespace App\Actions;

use App\Models\ShopInventoryMovement;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use App\Services\ShopInventoryUnitService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncShopProductInventory
{
    public function __construct(private readonly ShopInventoryUnitService $units) {}

    public function adjust(
        ShopProduct $product,
        ?ShopProductVariant $variant,
        int $quantityDelta,
        string $type,
        ?User $changedBy = null,
        ?Model $reference = null,
        ?string $note = null,
        ?array $unitIds = null,
    ): ShopInventoryMovement {
        return DB::transaction(function () use ($product, $variant, $quantityDelta, $type, $changedBy, $reference, $note, $unitIds): ShopInventoryMovement {
            $lockedProduct = $this->units->lockProduct($product);
            $lockedVariant = $variant ? $lockedProduct->allVariants()->lockForUpdate()->find($variant->getKey()) : null;
            if ($variant && ! $lockedVariant) {
                throw ValidationException::withMessages(['shop_product_variant_id' => 'Biến thể không thuộc sản phẩm này.']);
            }
            $stockTarget = $lockedVariant ?? $lockedProduct;
            $nextStock = (int) $stockTarget->stock + $quantityDelta;

            if ($nextStock < 0) {
                throw ValidationException::withMessages([
                    'quantity_delta' => "Tồn kho của {$product->name} không đủ cho thao tác này.",
                ]);
            }

            $this->units->syncStock($lockedProduct, $lockedVariant, $quantityDelta, $unitIds, $reference, restoreReferenced: $type === 'order_released');
            $stockTarget->update(['stock' => $nextStock]);

            if ($lockedVariant instanceof ShopProductVariant && $lockedProduct->has_variants) {
                $lockedProduct->update([
                    'stock' => (int) $lockedProduct->variants()->where('is_active', true)->sum('stock'),
                ]);
            }

            $product->setRawAttributes($lockedProduct->getAttributes(), sync: true);
            if ($variant && $lockedVariant) {
                $variant->setRawAttributes($lockedVariant->getAttributes(), sync: true);
            }

            return ShopInventoryMovement::query()->create([
                'shop_owner_id' => $product->shop_owner_id,
                'shop_product_id' => $product->getKey(),
                'shop_product_variant_id' => $variant?->getKey(),
                'changed_by' => $changedBy?->getKey(),
                'type' => $type,
                'quantity_delta' => $quantityDelta,
                'stock_after' => $nextStock,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'note' => $note,
            ]);
        }, attempts: 5);
    }

    public function recordInitial(
        ShopProduct $product,
        ?ShopProductVariant $variant,
        int $stock,
        User $changedBy,
    ): void {
        $this->units->synchronizeForProduct($product, $variant);
        if ($stock === 0) {
            return;
        }

        ShopInventoryMovement::query()->create([
            'shop_owner_id' => $product->shop_owner_id,
            'shop_product_id' => $product->getKey(),
            'shop_product_variant_id' => $variant?->getKey(),
            'changed_by' => $changedBy->getKey(),
            'type' => 'initial',
            'quantity_delta' => $stock,
            'stock_after' => $stock,
            'note' => 'Khởi tạo tồn kho sản phẩm.',
        ]);
    }

    public function reconcile(
        ShopProduct $product,
        ?ShopProductVariant $variant,
        int $previousStock,
        int $nextStock,
        User $changedBy,
    ): void {
        $this->units->synchronizeForProduct($product, $variant);
        $difference = $nextStock - $previousStock;

        if ($difference === 0) {
            return;
        }

        ShopInventoryMovement::query()->create([
            'shop_owner_id' => $product->shop_owner_id,
            'shop_product_id' => $product->getKey(),
            'shop_product_variant_id' => $variant?->getKey(),
            'changed_by' => $changedBy->getKey(),
            'type' => 'product_updated',
            'quantity_delta' => $difference,
            'stock_after' => $nextStock,
            'note' => 'Điều chỉnh khi cập nhật sản phẩm.',
        ]);
    }
}
