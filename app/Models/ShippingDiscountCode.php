<?php

namespace App\Models;

use Database\Factories\ShippingDiscountCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'shop_product_id', 'code', 'expires_at', 'used_at', 'shop_order_id'])]
class ShippingDiscountCode extends Model
{
    /** @use HasFactory<ShippingDiscountCodeFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function isAvailable(): bool
    {
        return $this->used_at === null && $this->shop_order_id === null && $this->expires_at->isFuture();
    }
}
