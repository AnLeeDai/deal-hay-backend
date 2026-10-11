<?php

namespace App\Models;

use Database\Factories\ShopProductOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['shop_product_id', 'name', 'name_normalized', 'position'])]
class ShopProductOption extends Model
{
    /** @use HasFactory<ShopProductOptionFactory> */
    use HasFactory, HasUuids;

    protected $attributes = ['position' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $option): void {
            $option->name = Str::squish((string) $option->name);
            $option->name_normalized = Str::lower(Str::ascii($option->name));
        });
    }

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ShopProductOptionValue::class)->orderBy('position');
    }
}
