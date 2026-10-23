<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['shop_stocktake_id', 'shop_product_id', 'shop_product_variant_id', 'code', 'product_name', 'variant_name', 'sku', 'expected_quantity', 'expected_movement_count', 'counted_quantity', 'is_unknown'])]
class ShopStocktakeItem extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['expected_quantity' => 'integer', 'expected_movement_count' => 'integer', 'counted_quantity' => 'integer', 'is_unknown' => 'boolean'];
    }
}
