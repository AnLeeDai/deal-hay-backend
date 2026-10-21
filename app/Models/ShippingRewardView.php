<?php

namespace App\Models;

use Database\Factories\ShippingRewardViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'shop_product_id', 'expires_at', 'redeemed_at', 'shipping_discount_code_id'])]
class ShippingRewardView extends Model
{
    /** @use HasFactory<ShippingRewardViewFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'redeemed_at' => 'datetime'];
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(ShippingDiscountCode::class, 'shipping_discount_code_id');
    }
}
