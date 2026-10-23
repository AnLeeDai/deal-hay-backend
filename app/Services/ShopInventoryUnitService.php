<?php

namespace App\Services;

use App\Models\ShopInventoryUnit;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopInventoryUnitService
{
    public function lockProduct(ShopProduct $product): ShopProduct
    {
        // SQLite ignores FOR UPDATE. Acquire its write lock before reading the
        // baseline so concurrent local requests cannot both backfill the stock.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('shop_products')->where('id', $product->getKey())->update(['id' => DB::raw('id')]);
        }

        return ShopProduct::query()->lockForUpdate()->findOrFail($product->getKey());
    }

    /** @return Builder<ShopInventoryUnit> */
    public function availableForProduct(ShopProduct $product, ?ShopProductVariant $variant = null): Builder
    {
        $query = $this->forTarget($product, $variant)->where('status', 'available');

        if (($variant && $variant->retired_at !== null) || (! $variant && $product->has_variants)) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /** @return Collection<int, ShopInventoryUnit> */
    public function ensureForProduct(ShopProduct $product, ?ShopProductVariant $variant = null): Collection
    {
        return DB::transaction(function () use ($product, $variant): Collection {
            [$lockedProduct, $lockedVariant] = $this->lockTarget($product, $variant);
            $this->reconcileTarget($lockedProduct, $lockedVariant);

            return $this->availableForProduct($lockedProduct, $lockedVariant)->orderBy('id')->get();
        }, attempts: 5);
    }

    public function synchronizeForProduct(ShopProduct $product, ?ShopProductVariant $variant = null): void
    {
        DB::transaction(function () use ($product, $variant): void {
            [$lockedProduct, $lockedVariant] = $this->lockTarget($product, $variant);
            $this->reconcileTarget($lockedProduct, $lockedVariant);
        }, attempts: 5);
    }

    /** Backfill old stock lazily without loading every physical unit into memory. */
    public function ensureForOwner(User $owner): void
    {
        ShopProduct::query()->where('shop_owner_id', $owner->getKey())->orderBy('id')->chunkById(100, function (Collection $products): void {
            foreach ($products as $product) {
                DB::transaction(function () use ($product): void {
                    [$lockedProduct] = $this->lockTarget($product, null, allowVariantParent: true);
                    if (! $lockedProduct->has_variants) {
                        $this->reconcileTarget($lockedProduct, null);

                        return;
                    }

                    $this->removeAll($lockedProduct, null);
                    foreach ($lockedProduct->allVariants()->orderBy('id')->lockForUpdate()->get() as $variant) {
                        $this->reconcileTarget($lockedProduct, $variant);
                    }
                }, attempts: 5);
            }
        });
    }

    /**
     * Apply unit changes before updating aggregate stock in the same transaction.
     * Explicit positive IDs restore known physical units; ordinary receipts mint new IDs.
     *
     * @param  list<string>|null  $unitIds
     */
    public function syncStock(
        ShopProduct $product,
        ?ShopProductVariant $variant,
        int $quantityDelta,
        ?array $unitIds = null,
        ?Model $reference = null,
        bool $restoreReferenced = false,
    ): void {
        DB::transaction(function () use ($product, $variant, $quantityDelta, $unitIds, $reference, $restoreReferenced): void {
            [$lockedProduct, $lockedVariant] = $this->lockTarget($product, $variant);
            if ($lockedVariant?->retired_at !== null) {
                if ($restoreReferenced && $reference instanceof ShopOrder && $quantityDelta > 0
                    && (string) $reference->shop_owner_id === (string) $lockedProduct->shop_owner_id) {
                    $this->releaseRetiredReservation($lockedProduct, $lockedVariant, $quantityDelta, $reference, $unitIds);

                    return;
                }
                throw ValidationException::withMessages(['shop_product_variant_id' => 'Biến thể đã ngừng sử dụng, không thể điều chỉnh tồn kho.']);
            }
            $stock = (int) ($lockedVariant ?? $lockedProduct)->stock;
            if ($stock + $quantityDelta < 0) {
                throw ValidationException::withMessages(['quantity_delta' => 'Tồn kho không đủ cho thao tác này.']);
            }
            $this->reconcileTarget($lockedProduct, $lockedVariant);

            if ($unitIds !== null) {
                if (count($unitIds) !== abs($quantityDelta)
                    || count(array_unique($unitIds)) !== count($unitIds)
                    || collect($unitIds)->contains(fn ($id): bool => ! is_string($id) || ! Str::isUuid($id))) {
                    throw ValidationException::withMessages(['unit_ids' => 'Số tem được chọn phải khớp với số lượng điều chỉnh và không được trùng.']);
                }
                $units = $this->forTarget($lockedProduct, $lockedVariant)
                    ->whereKey($unitIds)->where('status', $quantityDelta < 0 ? 'available' : 'removed')
                    ->orderBy('id')->lockForUpdate()->get();
                if ($units->count() !== count($unitIds)) {
                    throw ValidationException::withMessages(['unit_ids' => 'Tem sản phẩm không thuộc mặt hàng này hoặc trạng thái đã thay đổi.']);
                }
                if ($quantityDelta > 0) {
                    $this->assertRestorable($units, $reference, $restoreReferenced);
                }
                foreach (array_chunk($units->modelKeys(), 500) as $chunk) {
                    $this->forTarget($lockedProduct, $lockedVariant)->whereKey($chunk)->update(
                        $quantityDelta < 0 ? $this->removedAttributes($reference) : $this->availableAttributes(),
                    );
                }

                return;
            }

            if ($quantityDelta < 0) {
                $this->remove($lockedProduct, $lockedVariant, -$quantityDelta, $reference);

                return;
            }

            $remaining = $quantityDelta;
            if ($restoreReferenced && $reference !== null) {
                while ($remaining > 0) {
                    $ids = $this->forTarget($lockedProduct, $lockedVariant)
                        ->where('status', 'removed')->where('removed_reference_type', $reference->getMorphClass())
                        ->where('removed_reference_id', $reference->getKey())
                        ->orderBy('id')->limit(min(500, $remaining))->lockForUpdate()->pluck('id')->all();
                    if ($ids === []) {
                        break;
                    }
                    $this->forTarget($lockedProduct, $lockedVariant)->whereKey($ids)->update($this->availableAttributes());
                    $remaining -= count($ids);
                }
            }
            $this->createUnits($lockedProduct, $lockedVariant, $remaining);
        }, attempts: 5);
    }

    public function retireForProduct(ShopProduct $product, ?ShopProductVariant $variant = null): void
    {
        DB::transaction(function () use ($product, $variant): void {
            [$lockedProduct, $lockedVariant] = $this->lockTarget($product, $variant, allowVariantParent: true);
            $this->removeAll($lockedProduct, $lockedVariant);
        }, attempts: 5);
    }

    /** @return Builder<ShopInventoryUnit> */
    private function forTarget(ShopProduct $product, ?ShopProductVariant $variant): Builder
    {
        return ShopInventoryUnit::query()->where('shop_owner_id', $product->shop_owner_id)
            ->where('shop_product_id', $product->getKey())->where('shop_product_variant_id', $variant?->getKey());
    }

    /** @return array{ShopProduct, ?ShopProductVariant} */
    private function lockTarget(ShopProduct $product, ?ShopProductVariant $variant, bool $allowVariantParent = false): array
    {
        $lockedProduct = $this->lockProduct($product);
        $lockedVariant = $variant ? $lockedProduct->allVariants()->lockForUpdate()->find($variant->getKey()) : null;
        if (($variant && ! $lockedVariant) || (! $variant && $lockedProduct->has_variants && ! $allowVariantParent)) {
            throw ValidationException::withMessages(['shop_product_variant_id' => 'Vui lòng chọn đúng biến thể của sản phẩm.']);
        }

        return [$lockedProduct, $lockedVariant];
    }

    private function reconcileTarget(ShopProduct $product, ?ShopProductVariant $variant): void
    {
        if ($variant?->retired_at !== null) {
            $this->removeAll($product, $variant);

            return;
        }
        $count = $this->forTarget($product, $variant)->where('status', 'available')->count();
        $expected = max(0, (int) ($variant ?? $product)->stock);
        if ($expected > $count) {
            $this->createUnits($product, $variant, $expected - $count);
        } elseif ($expected < $count) {
            $this->remove($product, $variant, $count - $expected);
        }
    }

    private function createUnits(ShopProduct $product, ?ShopProductVariant $variant, int $quantity): void
    {
        while ($quantity > 0) {
            $batchSize = min(500, $quantity);
            $rows = [];
            $timestamp = now();
            for ($index = 0; $index < $batchSize; $index++) {
                $id = (string) Str::uuid7();
                $rows[] = [
                    'id' => $id, 'shop_owner_id' => $product->shop_owner_id,
                    'shop_product_id' => $product->getKey(), 'shop_product_variant_id' => $variant?->getKey(),
                    'sku' => 'DHU-'.Str::upper($id), 'status' => 'available',
                    'created_at' => $timestamp, 'updated_at' => $timestamp,
                ];
            }
            ShopInventoryUnit::query()->insert($rows);
            $quantity -= $batchSize;
        }
    }

    private function remove(ShopProduct $product, ?ShopProductVariant $variant, int $quantity, ?Model $reference = null): void
    {
        while ($quantity > 0) {
            $ids = $this->forTarget($product, $variant)->where('status', 'available')->orderBy('id')
                ->limit(min(500, $quantity))->lockForUpdate()->pluck('id')->all();
            if ($ids === []) {
                throw ValidationException::withMessages(['unit_ids' => 'Không đủ tem sản phẩm đang tồn kho. Vui lòng tải lại dữ liệu.']);
            }
            $this->forTarget($product, $variant)->whereKey($ids)->update($this->removedAttributes($reference));
            $quantity -= count($ids);
        }
    }

    private function removeAll(ShopProduct $product, ?ShopProductVariant $variant): void
    {
        $this->forTarget($product, $variant)->where('status', 'available')->update($this->removedAttributes());
    }

    private function removedAttributes(?Model $reference = null): array
    {
        return ['status' => 'removed', 'removed_at' => now(), 'removed_reference_type' => $reference?->getMorphClass(), 'removed_reference_id' => $reference?->getKey()];
    }

    private function availableAttributes(): array
    {
        return ['status' => 'available', 'removed_at' => null, 'removed_reference_type' => null, 'removed_reference_id' => null];
    }

    /** @param list<string>|null $unitIds */
    private function releaseRetiredReservation(ShopProduct $product, ShopProductVariant $variant, int $quantity, ShopOrder $order, ?array $unitIds): void
    {
        $this->removeAll($product, $variant);
        $query = $this->forTarget($product, $variant)->where('status', 'removed')
            ->where('removed_reference_type', $order->getMorphClass())->where('removed_reference_id', $order->getKey());
        if ($unitIds !== null) {
            if (count($unitIds) !== $quantity || count(array_unique($unitIds)) !== count($unitIds)) {
                throw ValidationException::withMessages(['unit_ids' => 'Số tem được chọn không khớp với hàng hoàn của đơn.']);
            }
            $query->whereKey($unitIds);
        }
        // Retired goods stay unavailable. Clearing the reservation records the
        // cancellation without issuing replacement identities or exposing labels.
        $ids = $query->orderBy('id')->limit($quantity)->lockForUpdate()->pluck('id')->all();
        if ($unitIds !== null && count($ids) !== count($unitIds)) {
            throw ValidationException::withMessages(['unit_ids' => 'Tem sản phẩm không thuộc hàng hoàn của đơn này.']);
        }
        foreach (array_chunk($ids, 500) as $chunk) {
            $this->forTarget($product, $variant)->whereKey($chunk)->update(['removed_reference_type' => null, 'removed_reference_id' => null]);
        }
    }

    /** @param Collection<int, ShopInventoryUnit> $units */
    private function assertRestorable(Collection $units, ?Model $reference, bool $restoreReferenced): void
    {
        $orderType = (new ShopOrder)->getMorphClass();
        $orderIds = $units->filter(fn (ShopInventoryUnit $unit): bool => $unit->removed_reference_type === $orderType
            && ! ($restoreReferenced && $reference instanceof ShopOrder && $unit->removed_reference_id === $reference->getKey()))
            ->pluck('removed_reference_id')->unique()->values();
        if ($orderIds->isEmpty()) {
            return;
        }
        // Cancelling an order releases its units before changing its status;
        // only that exact order_released operation may bypass this protection.
        $cancelledCount = ShopOrder::query()->whereKey($orderIds)->where('status', 'cancelled')->count();
        if ($cancelledCount !== $orderIds->count()) {
            throw ValidationException::withMessages(['unit_ids' => 'Tem sản phẩm đang thuộc đơn hàng chưa hủy; không thể đưa lại vào tồn kho khi kiểm kê.']);
        }
    }
}
