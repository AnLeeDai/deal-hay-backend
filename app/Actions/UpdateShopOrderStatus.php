<?php

namespace App\Actions;

use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateShopOrderStatus
{
    /** @var array<string, list<string>> */
    private const array ALLOWED_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipping', 'cancelled'],
        'shipping' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(private readonly SyncShopProductInventory $inventory) {}

    public function handle(User $shopOwner, ShopOrder $shopOrder, string $nextStatus): void
    {
        DB::transaction(function () use ($nextStatus, $shopOrder, $shopOwner): void {
            $order = ShopOrder::query()->with('items')->lockForUpdate()->findOrFail($shopOrder->getKey());

            if (! in_array($nextStatus, self::ALLOWED_TRANSITIONS[$order->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Không thể chuyển sang trạng thái đơn hàng đã chọn.']);
            }

            if ($nextStatus === 'cancelled') {
                $this->restoreOrderStock($shopOwner, $order);

                $coupon = $order->shop_coupon_id
                    ? ShopCoupon::query()->lockForUpdate()->find($order->shop_coupon_id)
                    : null;

                if ($coupon && $coupon->used_count > 0) {
                    $coupon->decrement('used_count');
                }
            }

            $order->update([
                'status' => $nextStatus,
                'completed_at' => $nextStatus === 'completed' ? now() : null,
                'cancelled_at' => $nextStatus === 'cancelled' ? now() : null,
            ]);
            app(NotifyShopOrder::class)->statusUpdated($order);
        }, attempts: 5);
    }

    private function restoreOrderStock(User $shopOwner, ShopOrder $order): void
    {
        $productIds = $order->items->pluck('shop_product_id')->unique()->sort()->values();
        $variantIds = $order->items->pluck('shop_product_variant_id')->filter()->unique()->sort()->values();
        $products = ShopProduct::query()
            ->whereKey($productIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (ShopProduct $product): string => (string) $product->getKey());
        $variants = ShopProductVariant::query()
            ->whereKey($variantIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (ShopProductVariant $variant): string => (string) $variant->getKey());

        foreach ($order->items->sortBy(fn ($item): string => $item->shop_product_id.':'.($item->shop_product_variant_id ?? '')) as $item) {
            /** @var ShopProduct $product */
            $product = $products->get((string) $item->shop_product_id)
                ?? throw ValidationException::withMessages(['items' => 'Sản phẩm của đơn hàng không còn tồn tại.']);
            $variant = $item->shop_product_variant_id
                ? $variants->get((string) $item->shop_product_variant_id)
                : null;
            $this->inventory->adjust(
                $product,
                $variant,
                (int) $item->quantity,
                'order_released',
                $shopOwner,
                $order,
                "Hoàn kho do huỷ đơn {$order->order_number}.",
            );
        }
    }
}
