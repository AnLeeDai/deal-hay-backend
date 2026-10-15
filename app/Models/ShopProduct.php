<?php

namespace App\Models;

use App\Models\Traits\HasRemoteMedia;
use Database\Factories\ShopProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['shop_owner_id', 'product_category_id', 'sku', 'sku_normalized', 'name', 'description', 'price', 'old_price', 'video_url', 'advertisement_video_url', 'advertisement_duration_seconds', 'stock', 'has_variants', 'label', 'image_url', 'is_active'])]
#[Hidden(['old_price'])]
class ShopProduct extends Model
{
    /** @use HasFactory<ShopProductFactory> */
    use HasFactory, HasRemoteMedia, HasUuids;

    protected array $mediaAttributes = ['image_url'];

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            $product->sku = filled($product->sku)
                ? Str::upper(trim((string) $product->sku))
                : null;
            $product->sku_normalized = $product->sku;
        });

        static::creating(function (self $product): void {
            if (filled($product->slug)) {
                return;
            }

            $baseSlug = Str::slug((string) $product->name) ?: 'san-pham';
            $slug = $baseSlug;
            $suffix = 2;

            while (self::query()
                ->where('shop_owner_id', $product->shop_owner_id)
                ->where('slug', $slug)
                ->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $product->slug = $slug;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'old_price' => 'integer',
            'advertisement_duration_seconds' => 'integer',
            'stock' => 'integer',
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected function category(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->relationLoaded('productCategory')
                ? $this->productCategory?->name
                : $this->productCategory()->value('name'),
        );
    }

    public function regularPrice(?int $salePrice = null): int
    {
        $salePrice ??= (int) $this->price;

        if ((int) $this->price <= 0 || (int) $this->old_price <= (int) $this->price) {
            return $salePrice;
        }

        return max($salePrice, (int) round($salePrice * (int) $this->old_price / (int) $this->price));
    }

    public function hasAdvertisement(): bool
    {
        return filled($this->advertisement_video_url)
            && (int) $this->advertisement_duration_seconds >= 5
            && (int) $this->old_price > (int) $this->price;
    }

    public function productAdViews(): HasMany
    {
        return $this->hasMany(ProductAdView::class);
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ShopProductImage::class)->orderBy('display_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ShopProductVariant::class)
            ->whereNull('retired_at')
            ->orderBy('position')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function allVariants(): HasMany
    {
        return $this->hasMany(ShopProductVariant::class)->orderBy('position')->orderBy('created_at');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ShopProductOption::class)->orderBy('position');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(ShopInventoryMovement::class)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ShopProductReview::class)->latest();
    }
}
