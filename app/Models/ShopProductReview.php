<?php

namespace App\Models;

use App\Enums\ShopReactionTypeEnum;
use App\Models\Traits\HasRemoteMedia;
use Database\Factories\ShopProductReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_product_id', 'customer_id', 'reaction', 'content', 'image_url', 'shop_reply', 'replied_at'])]
class ShopProductReview extends Model
{
    /** @use HasFactory<ShopProductReviewFactory> */
    use HasFactory, HasRemoteMedia, HasUuids;

    protected array $mediaAttributes = ['image_url'];

    protected function casts(): array
    {
        return [
            'reaction' => ShopReactionTypeEnum::class,
            'replied_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
