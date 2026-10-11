<?php

namespace App\Models;

use Database\Factories\ShopProductOptionValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['shop_product_option_id', 'value', 'value_normalized', 'position'])]
class ShopProductOptionValue extends Model
{
    /** @use HasFactory<ShopProductOptionValueFactory> */
    use HasFactory, HasUuids;

    protected $attributes = ['position' => 0];

    protected static function booted(): void
    {
        static::saving(function (self $optionValue): void {
            $optionValue->value = Str::squish((string) $optionValue->value);
            $optionValue->value_normalized = Str::lower(Str::ascii($optionValue->value));
        });
    }

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ShopProductOption::class, 'shop_product_option_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ShopProductVariant::class,
            'shop_product_variant_option_values',
            'shop_product_option_value_id',
            'shop_product_variant_id',
        );
    }
}
