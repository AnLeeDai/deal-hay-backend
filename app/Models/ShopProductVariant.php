<?php

namespace App\Models;

use Database\Factories\ShopProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['shop_product_id', 'shop_owner_id', 'sku', 'sku_normalized', 'combination_key', 'option_values_snapshot', 'color', 'size', 'price', 'stock', 'position', 'is_active', 'is_default', 'retired_at'])]
class ShopProductVariant extends Model
{
    /** @use HasFactory<ShopProductVariantFactory> */
    use HasFactory, HasUuids;

    protected $attributes = [
        'stock' => 0,
        'position' => 0,
        'is_active' => true,
        'is_default' => false,
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $variant): void {
            $variant->inventoryUnits()->where('status', 'available')->update([
                'status' => 'removed', 'removed_at' => now(),
                'removed_reference_type' => null, 'removed_reference_id' => null,
            ]);
        });

        static::saving(function (self $variant): void {
            $variant->sku = Str::upper(trim((string) $variant->sku));
            $variant->sku_normalized = $variant->sku;

            if (! filled($variant->shop_owner_id) && filled($variant->shop_product_id)) {
                $variant->shop_owner_id = ShopProduct::query()
                    ->whereKey($variant->shop_product_id)
                    ->value('shop_owner_id');
            }
        });

        static::created(function (self $variant): void {
            ShopProduct::query()
                ->whereKey($variant->shop_product_id)
                ->update(['has_variants' => true]);
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'position' => 'integer',
            'option_values_snapshot' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'retired_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ShopProductOptionValue::class,
            'shop_product_variant_option_values',
            'shop_product_variant_id',
            'shop_product_option_value_id',
        )->with('option')->orderBy('position');
    }

    /** @return array<string, string> */
    public function optionValueMap(): array
    {
        $snapshot = collect((array) $this->option_values_snapshot)
            ->mapWithKeys(fn (mixed $value, mixed $name): array => [(string) $name => (string) $value])
            ->all();

        if ($snapshot !== []) {
            return $snapshot;
        }

        $values = $this->relationLoaded('optionValues')
            ? $this->optionValues
            : $this->optionValues()->get();

        return $values
            ->sortBy(fn (ShopProductOptionValue $value): int => (int) $value->option->position)
            ->mapWithKeys(fn (ShopProductOptionValue $value): array => [
                (string) $value->option->name => (string) $value->value,
            ])
            ->all();
    }

    public function displayName(): ?string
    {
        $optionLabel = collect($this->optionValueMap())
            ->map(fn (string $value, string $name): string => "{$name}: {$value}")
            ->implode(' / ');

        if ($optionLabel !== '') {
            return $optionLabel;
        }

        $legacyLabel = collect([$this->color, $this->size])->filter()->join(' / ');

        return $legacyLabel !== '' ? $legacyLabel : null;
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(ShopInventoryMovement::class);
    }

    public function inventoryUnits(): HasMany
    {
        return $this->hasMany(ShopInventoryUnit::class, 'shop_product_variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
