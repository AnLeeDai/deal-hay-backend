<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_owner_id', 'name', 'status', 'stock_adjusted', 'completed_at'])]
class ShopStocktake extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['stock_adjusted' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopStocktakeItem::class);
    }
}
