<?php

namespace App\Models;

use Database\Factories\ShopOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_owner_id', 'customer_id', 'idempotency_key', 'request_fingerprint', 'shop_coupon_id', 'order_number', 'customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'shipping_province_code', 'shipping_ward_code', 'shipping_fee', 'shipping_discount_amount', 'shipping_total', 'status', 'subtotal', 'discount_amount', 'total', 'note', 'completed_at', 'cancelled_at'])]
#[Hidden(['idempotency_key', 'request_fingerprint'])]
class ShopOrder extends Model
{
    /** @use HasFactory<ShopOrderFactory> */
    use HasFactory, HasUuids;

    protected $attributes = ['status' => 'pending', 'discount_amount' => 0, 'shipping_fee' => 0, 'shipping_discount_amount' => 0, 'shipping_total' => 0];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'shipping_province_code' => 'integer',
            'shipping_ward_code' => 'integer',
            'shipping_fee' => 'integer',
            'shipping_discount_amount' => 'integer',
            'shipping_total' => 'integer',
            'total' => 'integer',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(ShopCoupon::class, 'shop_coupon_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
