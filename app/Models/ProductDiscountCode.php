<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shop_product_id', 'product_ad_view_id', 'code', 'original_price', 'sale_price', 'reward_type', 'reward_value', 'saved_at', 'expires_at', 'used_at', 'shop_order_id'])]
class ProductDiscountCode extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'original_price' => 'integer',
            'sale_price' => 'integer',
            'reward_value' => 'integer',
            'saved_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function adView(): BelongsTo
    {
        return $this->belongsTo(ProductAdView::class, 'product_ad_view_id');
    }

    public function discountedUnitPrice(int $regularPrice): int
    {
        if ($this->original_price <= 0 || $this->sale_price < 0) {
            return $regularPrice;
        }

        return min($regularPrice, intdiv(
            $regularPrice * $this->sale_price + intdiv($this->original_price, 2),
            $this->original_price,
        ));
    }

    public function discountForOrder(int $regularPrice, int $quantity): int
    {
        $subtotal = $regularPrice * $quantity;

        return match ($this->reward_type) {
            'fixed' => min($subtotal, max(0, (int) $this->reward_value)),
            'percentage' => min($subtotal, intdiv($subtotal * max(0, min(10000, (int) $this->reward_value)) + 5000, 10000)),
            null, 'legacy_ratio' => ($regularPrice - $this->discountedUnitPrice($regularPrice)) * $quantity,
            default => 0,
        };
    }
}
