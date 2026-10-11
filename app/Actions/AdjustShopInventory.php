<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustShopInventory
{
    public function __construct(private readonly SyncShopProductInventory $inventory) {}

    public function handle(User $shopOwner, ShopProduct $product, ?string $variantId, int $quantityDelta, string $note): void
    {
        abort_unless(
            $shopOwner->role === UserRoleEnum::SHOP_OWNER
            && (bool) $shopOwner->is_active
            && $product->shop_owner_id === $shopOwner->getKey(),
            403,
        );

        DB::transaction(function () use ($note, $product, $quantityDelta, $shopOwner, $variantId): void {
            $lockedProduct = ShopProduct::query()
                ->whereBelongsTo($shopOwner, 'shopOwner')
                ->lockForUpdate()
                ->findOrFail($product->getKey());
            $variant = $variantId ? $lockedProduct->allVariants()->lockForUpdate()->find($variantId) : null;

            if ($variantId && $variant === null) {
                throw ValidationException::withMessages(['shop_product_variant_id' => 'Biến thể không thuộc sản phẩm này.']);
            }

            if (! $variantId && $lockedProduct->has_variants) {
                throw ValidationException::withMessages(['shop_product_variant_id' => 'Vui lòng chọn biến thể cần điều chỉnh.']);
            }

            $this->inventory->adjust(
                $lockedProduct,
                $variant,
                $quantityDelta,
                'manual_adjustment',
                $shopOwner,
                note: $note,
            );
        }, attempts: 5);
    }
}
