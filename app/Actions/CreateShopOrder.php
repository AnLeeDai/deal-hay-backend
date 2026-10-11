<?php

namespace App\Actions;

use App\Enums\UserRoleEnum;
use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CreateShopOrder
{
    public function __construct(private readonly SyncShopProductInventory $inventory) {}

    /** @param array<string, mixed> $data */
    public function handle(User $shopOwner, array $data): bool
    {
        abort_unless(
            $shopOwner->role === UserRoleEnum::SHOP_OWNER && (bool) $shopOwner->is_active,
            403,
        );

        $idempotencyKey = (string) $data['idempotency_key'];
        $requestFingerprint = $this->orderFingerprint($data);
        $existingOrder = $this->findIdempotentOrder($shopOwner, $idempotencyKey);

        if ($existingOrder !== null) {
            $this->assertOrderFingerprint($existingOrder, $requestFingerprint);

            return true;
        }

        $replayed = false;

        try {
            DB::transaction(function () use ($data, $idempotencyKey, $requestFingerprint, &$replayed, $shopOwner): void {
                $reservedQuantities = [];
                $validatedItems = collect($data['items']);
                $skus = $validatedItems
                    ->pluck('sku')
                    ->map(fn (mixed $sku): string => (string) $sku)
                    ->unique()
                    ->sort()
                    ->values();
                $products = ShopProduct::query()
                    ->whereBelongsTo($shopOwner, 'shopOwner')
                    ->where(function ($query) use ($skus): void {
                        $query
                            ->whereIn('sku_normalized', $skus)
                            ->orWhereHas('allVariants', fn ($variantQuery) => $variantQuery->whereIn('sku_normalized', $skus));
                    })
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(fn (ShopProduct $product): string => (string) $product->getKey());

                $existingOrder = $this->findIdempotentOrder($shopOwner, $idempotencyKey);

                if ($existingOrder !== null) {
                    $this->assertOrderFingerprint($existingOrder, $requestFingerprint);
                    $replayed = true;

                    return;
                }

                $variants = ShopProductVariant::query()
                    ->whereBelongsTo($shopOwner, 'shopOwner')
                    ->whereIn('shop_product_id', $products->keys())
                    ->whereIn('sku_normalized', $skus)
                    ->with('optionValues.option')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $productsBySku = $products
                    ->filter(fn (ShopProduct $product): bool => filled($product->sku_normalized))
                    ->groupBy(fn (ShopProduct $product): string => (string) $product->sku_normalized);
                $variantsBySku = $variants
                    ->groupBy(fn (ShopProductVariant $variant): string => (string) $variant->sku_normalized);

                $items = $validatedItems->map(function (array $item, int $itemIndex) use ($products, $productsBySku, &$reservedQuantities, $variantsBySku): array {
                    $sku = (string) $item['sku'];
                    $productMatches = $productsBySku->get($sku, collect());
                    $variantMatches = $variantsBySku->get($sku, collect());
                    $matchCount = $productMatches->count() + $variantMatches->count();

                    if ($matchCount === 0) {
                        throw ValidationException::withMessages([
                            "items.{$itemIndex}.sku" => 'Không tìm thấy SKU trong cửa hàng.',
                        ]);
                    }

                    if ($matchCount > 1) {
                        throw ValidationException::withMessages([
                            "items.{$itemIndex}.sku" => 'SKU không xác định duy nhất trong cửa hàng.',
                        ]);
                    }

                    /** @var ShopProductVariant|null $variant */
                    $variant = $variantMatches->first();
                    /** @var ShopProduct|null $product */
                    $product = $variant instanceof ShopProductVariant
                        ? $products->get((string) $variant->shop_product_id)
                        : $productMatches->first();

                    if (! $product instanceof ShopProduct) {
                        throw ValidationException::withMessages([
                            "items.{$itemIndex}.sku" => 'Không tìm thấy SKU trong cửa hàng.',
                        ]);
                    }

                    if (! $product->is_active) {
                        throw ValidationException::withMessages([
                            "items.{$itemIndex}.sku" => "Sản phẩm {$product->name} đang bị ẩn.",
                        ]);
                    }

                    if ($product->has_variants && ! $variant instanceof ShopProductVariant) {
                        throw ValidationException::withMessages([
                            "items.{$itemIndex}.sku" => 'Đây là SKU sản phẩm gốc; vui lòng nhập SKU biến thể.',
                        ]);
                    }

                    if ($variant instanceof ShopProductVariant && (! $variant->is_active || $variant->retired_at !== null)) {
                        throw ValidationException::withMessages([
                            "items.{$itemIndex}.sku" => "Biến thể {$variant->sku} đang ngừng bán.",
                        ]);
                    }

                    $quantity = (int) $item['quantity'];
                    $stock = (int) ($variant?->stock ?? $product->stock);
                    $stockKey = $product->getKey().':'.($variant?->getKey() ?? 'base');
                    $reservedQuantities[$stockKey] = ($reservedQuantities[$stockKey] ?? 0) + $quantity;

                    if ($reservedQuantities[$stockKey] > $stock) {
                        throw ValidationException::withMessages([
                            "items.{$itemIndex}.quantity" => "Tồn kho của {$product->name} chỉ còn {$stock}.",
                        ]);
                    }

                    $unitPrice = (int) ($variant?->price ?? $product->price);

                    return compact('product', 'variant', 'quantity', 'unitPrice') + ['subtotal' => $unitPrice * $quantity];
                });

                $subtotal = (int) $items->sum('subtotal');
                $coupon = $this->resolveCoupon($shopOwner, $data['coupon_code'] ?? null, $subtotal);
                $discount = $coupon?->discountFor($subtotal) ?? 0;
                $order = ShopOrder::query()->create([
                    'shop_owner_id' => $shopOwner->getKey(),
                    'idempotency_key' => $idempotencyKey,
                    'request_fingerprint' => $requestFingerprint,
                    'shop_coupon_id' => $coupon?->getKey(),
                    'order_number' => 'DH-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'] ?? null,
                    'customer_phone' => $data['customer_phone'],
                    'shipping_address' => $data['shipping_address'],
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total' => $subtotal - $discount,
                    'note' => $data['note'] ?? null,
                ]);

                foreach ($items as $item) {
                    $variantName = $item['variant']
                        ? Str::limit((string) $item['variant']->displayName(), 255, '')
                        : null;
                    $order->items()->create([
                        'shop_product_id' => $item['product']->getKey(),
                        'shop_product_variant_id' => $item['variant']?->getKey(),
                        'product_name' => $item['product']->name,
                        'variant_name' => $variantName,
                        'sku' => $item['variant']?->sku ?? $item['product']->sku,
                        'unit_price' => $item['unitPrice'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                    ]);
                    $this->inventory->adjust(
                        $item['product'],
                        $item['variant'],
                        -$item['quantity'],
                        'order_reserved',
                        $shopOwner,
                        $order,
                        "Giữ kho cho đơn {$order->order_number}.",
                    );
                }

                $coupon?->increment('used_count');
            }, attempts: 5);
        } catch (UniqueConstraintViolationException $exception) {
            $existingOrder = $this->findIdempotentOrder($shopOwner, $idempotencyKey);

            if ($existingOrder === null) {
                throw $exception;
            }

            $this->assertOrderFingerprint($existingOrder, $requestFingerprint);
            $replayed = true;
        }

        return $replayed;
    }

    private function resolveCoupon(User $shopOwner, ?string $code, int $subtotal): ?ShopCoupon
    {
        if (! filled($code)) {
            return null;
        }

        $coupon = ShopCoupon::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->where('code', mb_strtoupper(trim((string) $code)))
            ->lockForUpdate()
            ->first();

        if (! $coupon?->isAvailable($subtotal)) {
            throw ValidationException::withMessages(['coupon_code' => 'Coupon không hợp lệ hoặc không đủ điều kiện áp dụng.']);
        }

        return $coupon;
    }

    /** @param array<string, mixed> $data */
    private function orderFingerprint(array $data): string
    {
        $payload = Arr::except($data, ['idempotency_key']);
        $payload['items'] = collect($payload['items'])
            ->map(fn (array $item): array => [
                'sku' => (string) $item['sku'],
                'quantity' => (int) $item['quantity'],
            ])
            ->sortBy(fn (array $item): string => $item['sku'])
            ->values()
            ->all();

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function findIdempotentOrder(User $shopOwner, string $idempotencyKey): ?ShopOrder
    {
        return ShopOrder::query()
            ->whereBelongsTo($shopOwner, 'shopOwner')
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    private function assertOrderFingerprint(ShopOrder $order, string $requestFingerprint): void
    {
        if ($order->request_fingerprint !== null && ! hash_equals($order->request_fingerprint, $requestFingerprint)) {
            throw new ConflictHttpException('Idempotency-Key đã được sử dụng cho một yêu cầu khác.');
        }
    }
}
