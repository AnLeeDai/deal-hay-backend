<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ProductAdView;
use App\Models\ProductDiscountCode;
use App\Models\RewardedTaskConversion;
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
        if (collect(['discount_code', 'discount_codes', 'advertisement_view_id'])->filter(fn ($field) => filled($data[$field] ?? null))->count() > 1) {
            throw ValidationException::withMessages(['discount_codes' => 'Không được kết hợp các định dạng mã ưu đãi trong cùng yêu cầu.']);
        }
        $fingerprintData = [
            $customer->getKey(), $product->getKey(), $data['variant_id'] ?? null, (int) $data['quantity'],
            $data['customer_name'], $data['customer_phone'], $data['shipping_address'],
            (int) $data['shipping_province_code'], (int) $data['shipping_ward_code'],
            $data['note'] ?? null, $data['advertisement_view_id'] ?? null,
        ];
        if (filled($data['discount_code'] ?? null)) {
            $fingerprintData[] = $data['discount_code'];
        }
        if (filled($data['discount_codes'] ?? null)) {
            $fingerprintData[] = $data['discount_codes'];
        }
        $fingerprint = hash('sha256', json_encode($fingerprintData, JSON_THROW_ON_ERROR));

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
            if (filled($data['advertisement_view_id'] ?? null)) {
                throw ValidationException::withMessages(['advertisement_view_id' => 'Phiên quảng cáo cũ không còn được dùng trực tiếp. Vui lòng chọn mã đã lưu trong ví.']);
            }
            $selectedCodes = $data['discount_codes'] ?? (filled($data['discount_code'] ?? null) ? [$data['discount_code']] : []);
            $availableCodes = ProductDiscountCode::query()->whereIn('code', $selectedCodes)->orderBy('id')->lockForUpdate()->get()->keyBy('code');
            $subtotal = $regularPrice * $quantity;
            $remaining = $subtotal;
            $redeemed = [];
            foreach ($selectedCodes as $selectedCode) {
                $code = $availableCodes->get($selectedCode);
                if (! $code || $code->user_id !== $customer->getKey() || $code->shop_product_id !== $product->getKey()
                    || $code->saved_at === null || $code->used_at !== null || $code->shop_order_id !== null || ! $code->expires_at->isFuture()) {
                    throw ValidationException::withMessages(['discount_code' => 'Mã giảm giá không hợp lệ, chưa lưu, đã hết hạn hoặc đã sử dụng.']);
                }
                $view = $code->product_ad_view_id !== null
                    ? ProductAdView::query()->lockForUpdate()->find($code->product_ad_view_id)
                    : null;
                $verifiedTaskReward = $code->product_ad_view_id === null && RewardedTaskConversion::query()
                    ->where('product_discount_code_id', $code->getKey())
                    ->whereHas('session', fn ($query) => $query->where('user_id', $customer->getKey())->where('shop_product_id', $product->getKey()))
                    ->lockForUpdate()->first() !== null;
                if (! $verifiedTaskReward && (! $view || $view->shop_order_id !== null || $view->completed_at === null
                    || $view->user_id !== $customer->getKey() || $view->shop_product_id !== $product->getKey())) {
                    throw ValidationException::withMessages(['discount_code' => 'Ưu đãi của mã giảm giá đã được sử dụng hoặc không hợp lệ.']);
                }
                $contribution = min($remaining, $code->discountForOrder($regularPrice, $quantity));
                if ($contribution > 0) {
                    $remaining -= $contribution;
                    $redeemed[] = ['code' => $code, 'view' => $view];
                }
            }

            $discount = $subtotal - $remaining;
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
            foreach ($redeemed as $reward) {
                $reward['view']?->update(['shop_order_id' => $order->getKey()]);
                $reward['code']->update(['shop_order_id' => $order->getKey(), 'used_at' => now()]);
            }

            return $order;
        }, attempts: 5);
    }
}
