<?php

namespace App\Actions;

use App\Models\ProductCategory;
use App\Models\ShopInventoryMovement;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\User;
use App\Services\UserPresenceService;
use Illuminate\Support\Carbon;

class GetShopManagementData
{
    public function __construct(
        private readonly GetShopEngagementMetrics $engagementMetrics,
        private readonly GetShopProductReviews $productReviews,
        private readonly UserPresenceService $presence,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $shopOwner): array
    {
        $userPayload = $shopOwner->toArray();
        $userPayload['online_status'] = $this->onlineStatus($shopOwner);

        return [
            'auth' => ['user' => $userPayload],
            'products' => $this->products($shopOwner),
            'categories' => $this->categories($shopOwner),
            'coupons' => $this->coupons($shopOwner),
            'orders' => $this->orders($shopOwner),
            'inventoryMovements' => $this->inventoryMovements($shopOwner),
            'shopMetrics' => $this->metrics($shopOwner),
            'productReviews' => $this->productReviews->forShop($shopOwner),
        ];
    }

    /** @return array{is_online: bool, label: string, last_activity_at: int|null} */
    private function onlineStatus(User $user): array
    {
        $lastActivity = $this->presence->lastSeenAt((string) $user->getKey()) ?? now()->timestamp;
        $isOnline = $this->presence->isOnline((string) $user->getKey());

        return [
            'is_online' => $isOnline,
            'label' => $isOnline ? 'Đang online' : 'Hoạt động '.Carbon::createFromTimestamp($lastActivity)->diffForHumans(),
            'last_activity_at' => $lastActivity,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function products(User $shopOwner): array
    {
        return ShopProduct::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->with(['images', 'options.values', 'productCategory:id,name', 'allVariants.optionValues.option'])
            ->latest()
            ->get()
            ->map(function (ShopProduct $product): array {
                $payload = $product->toArray();

                return [
                    'id' => (string) $payload['id'],
                    'sku' => $product->sku,
                    'name' => (string) $payload['name'],
                    'category' => (string) $product->productCategory->name,
                    'description' => $payload['description'],
                    'price' => (int) $payload['price'],
                    'stock' => (int) $payload['stock'],
                    'has_variants' => (bool) $product->has_variants,
                    'label' => $payload['label'],
                    'image_url' => $payload['image_url'],
                    'is_active' => (bool) $payload['is_active'],
                    'images' => $product->images->map(function ($image): array {
                        $imagePayload = $image->toArray();

                        return [
                            'id' => (string) $image->id,
                            'image_url' => (string) $imagePayload['image_url'],
                            'display_order' => (int) $image->display_order,
                        ];
                    })->all(),
                    'options' => $product->options->map(fn ($option): array => [
                        'id' => (string) $option->id,
                        'name' => (string) $option->name,
                        'values' => $option->values->pluck('value')->map('strval')->all(),
                    ])->all(),
                    'variants' => $product->allVariants->whereNull('retired_at')->map(fn ($variant): array => [
                        'id' => (string) $variant->id,
                        'sku' => (string) $variant->sku,
                        'color' => $variant->color,
                        'size' => $variant->size,
                        'option_values' => $variant->optionValueMap(),
                        'price' => $variant->price !== null ? (int) $variant->price : null,
                        'stock' => (int) $variant->stock,
                        'is_active' => (bool) $variant->is_active,
                        'is_default' => (bool) $variant->is_default,
                    ])->values()->all(),
                    'retired_variants' => $product->allVariants->whereNotNull('retired_at')->map(fn ($variant): array => [
                        'id' => (string) $variant->id,
                        'sku' => (string) $variant->sku,
                        'color' => $variant->color,
                        'size' => $variant->size,
                        'option_values' => $variant->optionValueMap(),
                        'variant_label' => $variant->displayName(),
                        'price' => $variant->price !== null ? (int) $variant->price : null,
                        'stock' => (int) $variant->stock,
                        'is_active' => false,
                        'is_default' => false,
                        'retired_at' => $variant->retired_at?->toISOString(),
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function coupons(User $shopOwner): array
    {
        return $shopOwner->shopCoupons()->latest()->get()->map(fn ($coupon): array => [
            'id' => (string) $coupon->id,
            'code' => (string) $coupon->code,
            'name' => (string) $coupon->name,
            'discount_type' => (string) $coupon->discount_type,
            'discount_value' => (int) $coupon->discount_value,
            'minimum_order_amount' => (int) $coupon->minimum_order_amount,
            'maximum_discount_amount' => $coupon->maximum_discount_amount !== null ? (int) $coupon->maximum_discount_amount : null,
            'usage_limit' => $coupon->usage_limit !== null ? (int) $coupon->usage_limit : null,
            'used_count' => (int) $coupon->used_count,
            'starts_at' => $coupon->starts_at?->toISOString(),
            'ends_at' => $coupon->ends_at?->toISOString(),
            'is_active' => (bool) $coupon->is_active,
        ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function orders(User $shopOwner): array
    {
        return ShopOrder::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->with('items')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ShopOrder $order): array => [
                'id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'customer_name' => (string) $order->customer_name,
                'customer_phone' => (string) $order->customer_phone,
                'shipping_address' => (string) $order->shipping_address,
                'status' => (string) $order->status,
                'subtotal' => (int) $order->subtotal,
                'discount_amount' => (int) $order->discount_amount,
                'total' => (int) $order->total,
                'note' => $order->note,
                'created_at' => $order->created_at?->toISOString(),
                'items' => $order->items->map(fn ($item): array => [
                    'id' => (string) $item->id,
                    'product_name' => (string) $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (int) $item->unit_price,
                    'subtotal' => (int) $item->subtotal,
                ])->all(),
            ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function inventoryMovements(User $shopOwner): array
    {
        return ShopInventoryMovement::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->with(['product', 'variant.optionValues.option'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (ShopInventoryMovement $movement): array => [
                'id' => (string) $movement->id,
                'product_name' => (string) $movement->product->name,
                'variant_name' => $movement->variant?->displayName(),
                'type' => (string) $movement->type,
                'quantity_delta' => (int) $movement->quantity_delta,
                'stock_after' => (int) $movement->stock_after,
                'note' => $movement->note,
                'created_at' => $movement->created_at?->toISOString(),
            ])->all();
    }

    /** @return list<array{id: string, name: string, display_order: int, is_active: bool}> */
    private function categories(User $shopOwner): array
    {
        $assignedCategories = $shopOwner->productCategories()
            ->where('is_active', true)
            ->orderByPivot('display_order')
            ->get();
        $unassignedCategories = ProductCategory::query()
            ->where('is_active', true)
            ->whereKeyNot($assignedCategories->modelKeys())
            ->orderBy('name')
            ->get();

        return $assignedCategories
            ->concat($unassignedCategories)
            ->map(fn (ProductCategory $category, int $index): array => [
                'id' => (string) $category->id,
                'name' => (string) $category->name,
                'display_order' => $index + 1,
                'is_active' => (bool) $category->is_active,
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function metrics(User $shopOwner): array
    {
        $orders = ShopOrder::query()->whereBelongsTo($shopOwner, 'shopOwner');
        $completedOrders = (clone $orders)->where('status', 'completed');

        return [
            ...$this->engagementMetrics->handle($shopOwner),
            'total_orders_count' => (clone $orders)->count(),
            'completed_orders_count' => (clone $completedOrders)->count(),
            'open_orders_count' => (clone $orders)->whereIn('status', ['pending', 'confirmed', 'processing', 'shipping'])->count(),
            'total_revenue' => (int) (clone $completedOrders)->sum('total'),
            'total_stock' => (int) ShopProduct::query()->whereBelongsTo($shopOwner, 'shopOwner')->sum('stock'),
            'active_coupons_count' => $shopOwner->shopCoupons()->where('is_active', true)->count(),
        ];
    }
}
