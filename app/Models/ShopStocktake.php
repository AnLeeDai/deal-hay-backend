<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_owner_id', 'name', 'status', 'stock_adjusted', 'completed_at', 'counting_mode', 'saved_at', 'confirmed_by', 'confirmed_at', 'confirmation_hash', 'financial_summary'])]
class ShopStocktake extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['stock_adjusted' => 'boolean', 'completed_at' => 'datetime', 'saved_at' => 'datetime', 'confirmed_at' => 'datetime', 'financial_summary' => 'array', 'revision' => 'integer'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopStocktakeItem::class);
    }
}
