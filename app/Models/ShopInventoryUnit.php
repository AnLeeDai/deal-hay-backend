<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['shop_owner_id', 'shop_product_id', 'shop_product_variant_id', 'status', 'removed_at', 'removed_reference_type', 'removed_reference_id'])]
class ShopInventoryUnit extends Model
{
    use HasUuids;

    protected $attributes = ['status' => 'available'];

    protected static function booted(): void
    {
        static::creating(function (self $unit): void {
            $unit->sku = 'DHU-'.Str::upper((string) $unit->getKey());
        });
    }

    protected function casts(): array
    {
        return ['removed_at' => 'datetime'];
    }

    protected function code(): Attribute
    {
        return Attribute::make(get: fn (): string => (string) $this->sku);
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
