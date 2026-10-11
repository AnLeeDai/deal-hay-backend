<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_order_id', 'shop_product_id', 'shop_product_variant_id', 'product_name', 'variant_name', 'sku', 'unit_price', 'quantity', 'subtotal'])]
class ShopOrderItem extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'quantity' => 'integer', 'subtotal' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'shop_product_variant_id');
    }
}
