<?php

namespace App\Actions;

use App\Models\ShopInventoryMovement;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SyncShopProductInventory
{
    public function adjust(
        ShopProduct $product,
        ?ShopProductVariant $variant,
        int $quantityDelta,
        string $type,
        ?User $changedBy = null,
        ?Model $reference = null,
        ?string $note = null,
    ): ShopInventoryMovement {
        $stockTarget = $variant ?? $product;
        $nextStock = (int) $stockTarget->stock + $quantityDelta;

        if ($nextStock < 0) {
            throw ValidationException::withMessages([
                'quantity_delta' => "Tồn kho của {$product->name} không đủ cho thao tác này.",
            ]);
        }

        $stockTarget->update(['stock' => $nextStock]);

        if ($variant instanceof ShopProductVariant && $product->has_variants) {
            $product->update([
                'stock' => (int) $product->variants()->where('is_active', true)->sum('stock'),
            ]);
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
    }

    public function recordInitial(
        ShopProduct $product,
        ?ShopProductVariant $variant,
        int $stock,
        User $changedBy,
    ): void {
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
