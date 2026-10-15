<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'shop_product_id', 'video_url', 'duration_seconds', 'completed_at', 'expires_at', 'shop_order_id', 'provider', 'campaign_id', 'ad_type', 'reward_type', 'reward_value', 'verified_at', 'verification_reference', 'provider_session'])]
class ProductAdView extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'expires_at' => 'datetime', 'duration_seconds' => 'integer', 'verified_at' => 'datetime', 'reward_value' => 'integer', 'provider_session' => 'array'];
    }
}
