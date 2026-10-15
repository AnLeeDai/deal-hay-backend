<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ProductAdView;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use App\Services\AddressService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateCustomerProductOrder
{
    public function __construct(private readonly SyncShopProductInventory $inventory, private readonly AddressService $addresses) {}

    /** @param array<string, mixed> $data */
    public function handle(User $customer, User $shop, ShopProduct $product, array $data): ShopOrder
    {
        abort_unless($customer->is_active && $shop->getKey() !== $customer->getKey(), 403);
        $fingerprint = hash('sha256', json_encode([
            $customer->getKey(), $product->getKey(), $data['variant_id'] ?? null, (int) $data['quantity'],
            $data['customer_name'], $data['customer_phone'], $data['shipping_address'],
            (int) $data['shipping_province_code'], (int) $data['shipping_ward_code'],
            $data['note'] ?? null, $data['advertisement_view_id'] ?? null,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($customer, $shop, $product, $data, $fingerprint): ShopOrder {
            // Serialize checkout with product edits and inventory reservations.
            $shop = User::query()->lockForUpdate()->findOrFail($shop->getKey());
            abort_unless($shop->role === UserRoleEnum::SHOP_OWNER && $shop->is_active, 404);
            $product = ShopProduct::query()->where('shop_owner_id', $shop->getKey())->lockForUpdate()->findOrFail($product->getKey());
            $existing = ShopOrder::query()->where('shop_owner_id', $shop->getKey())
                ->where('idempotency_key', $data['idempotency_key'])->first();

            if ($existing) {
                if ($existing->customer_id !== $customer->getKey() || ! hash_equals((string) $existing->request_fingerprint, $fingerprint)) {
                    throw new ConflictHttpException('Mã yêu cầu đã được dùng cho một đơn hàng khác.');
                }

                return $existing;
            }

            abort_unless($product->is_active, 404);
            $variant = filled($data['variant_id'] ?? null)
                ? ShopProductVariant::query()->where('shop_product_id', $product->getKey())
                    ->where('is_active', true)->whereNull('retired_at')->lockForUpdate()->find($data['variant_id'])
                : null;
            if (($product->has_variants && ! $variant) || (filled($data['variant_id'] ?? null) && ! $variant)) {
                throw ValidationException::withMessages(['variant_id' => 'Vui lòng chọn biến thể đang bán của sản phẩm.']);
            }

            $quantity = (int) $data['quantity'];
            if ($quantity > (int) ($variant?->stock ?? $product->stock)) {
                throw ValidationException::withMessages(['quantity' => 'Số lượng đặt vượt quá tồn kho.']);
            }

            $salePrice = (int) ($variant?->price ?? $product->price);
            $regularPrice = $product->regularPrice($salePrice);
            if ($regularPrice > intdiv(4294967295, $quantity)) {
                throw ValidationException::withMessages(['quantity' => 'Tổng giá trị đơn hàng vượt quá giới hạn cho phép. Vui lòng giảm số lượng.']);
            }
            $view = null;
            if (filled($data['advertisement_view_id'] ?? null)) {
                $view = ProductAdView::query()->lockForUpdate()->find($data['advertisement_view_id']);
                if (! $product->hasAdvertisement() || ! $view || $view->user_id !== $customer->getKey()
                    || $view->shop_product_id !== $product->getKey() || $view->completed_at === null
                    || $view->expires_at->isPast() || $view->shop_order_id !== null
                    || $view->video_url !== $product->advertisement_video_url
                    || $view->duration_seconds !== (int) $product->advertisement_duration_seconds) {
                    throw ValidationException::withMessages(['advertisement_view_id' => 'Ưu đãi chưa được mở, đã hết hạn hoặc đã sử dụng.']);
                }
            }

            $subtotal = $regularPrice * $quantity;
            $discount = $view ? ($regularPrice - $salePrice) * $quantity : 0;
            $order = ShopOrder::query()->create([
                'shop_owner_id' => $shop->getKey(),
                'customer_id' => $customer->getKey(),
                'idempotency_key' => $data['idempotency_key'],
                'request_fingerprint' => $fingerprint,
                'order_number' => 'DH-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                'customer_name' => $data['customer_name'],
                'customer_email' => $customer->email,
                'customer_phone' => $data['customer_phone'],
                'shipping_address' => $this->addresses->formatShippingAddress($data['shipping_address'], (int) $data['shipping_province_code'], (int) $data['shipping_ward_code']),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total' => $subtotal - $discount,
                'note' => $data['note'] ?? null,
            ]);
            $order->items()->create([
                'shop_product_id' => $product->getKey(),
                'shop_product_variant_id' => $variant?->getKey(),
                'product_name' => $product->name,
                'variant_name' => $variant ? Str::limit($variant->displayName(), 255, '') : null,
                'sku' => $variant?->sku ?? $product->sku,
                'unit_price' => $regularPrice,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ]);
            $this->inventory->adjust($product, $variant, -$quantity, 'order_reserved', $customer, $order, "Giữ kho cho đơn {$order->order_number}.");
            $view?->update(['shop_order_id' => $order->getKey()]);

            return $order;
        }, attempts: 5);
    }
}
