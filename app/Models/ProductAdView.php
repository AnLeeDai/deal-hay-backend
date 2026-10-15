<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'shop_product_id', 'video_url', 'duration_seconds', 'completed_at', 'expires_at', 'shop_order_id'])]
class ProductAdView extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'expires_at' => 'datetime', 'duration_seconds' => 'integer'];
    }
}
