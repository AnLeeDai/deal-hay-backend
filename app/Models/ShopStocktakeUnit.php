<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['shop_stocktake_id', 'shop_stocktake_item_id', 'inventory_unit_id', 'sku', 'code', 'expected', 'counted'])]
class ShopStocktakeUnit extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['expected' => 'boolean', 'counted' => 'boolean'];
    }
}
