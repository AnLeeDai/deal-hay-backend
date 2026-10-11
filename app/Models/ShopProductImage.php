<?php

namespace App\Models;

use App\Models\Traits\HasRemoteMedia;
use Database\Factories\ShopProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_product_id', 'image_url', 'display_order'])]
class ShopProductImage extends Model
{
    /** @use HasFactory<ShopProductImageFactory> */
    use HasFactory, HasRemoteMedia, HasUuids;

    protected array $mediaAttributes = ['image_url'];

    protected function casts(): array
    {
        return ['display_order' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }
}
