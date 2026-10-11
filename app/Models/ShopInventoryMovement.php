<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_owner_id', 'shop_product_id', 'shop_product_variant_id', 'changed_by', 'type', 'quantity_delta', 'stock_after', 'reference_type', 'reference_id', 'note'])]
class ShopInventoryMovement extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['quantity_delta' => 'integer', 'stock_after' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class, 'shop_product_variant_id');
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }
}
