<?php

namespace App\Models;

use Database\Factories\ShopCouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_owner_id', 'code', 'name', 'discount_type', 'discount_value', 'minimum_order_amount', 'maximum_discount_amount', 'usage_limit', 'used_count', 'starts_at', 'ends_at', 'is_active'])]
class ShopCoupon extends Model
{
    /** @use HasFactory<ShopCouponFactory> */
    use HasFactory, HasUuids;

    protected $attributes = ['minimum_order_amount' => 0, 'used_count' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'minimum_order_amount' => 'integer',
            'maximum_discount_amount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function isAvailable(int $subtotal): bool
    {
        return $this->is_active
            && $subtotal >= $this->minimum_order_amount
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit)
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function discountFor(int $subtotal): int
    {
        $discount = $this->discount_type === 'percent'
            ? (int) floor($subtotal * $this->discount_value / 100)
            : $this->discount_value;

        if ($this->maximum_discount_amount !== null) {
            $discount = min($discount, $this->maximum_discount_amount);
        }

        return min($subtotal, $discount);
    }
}
