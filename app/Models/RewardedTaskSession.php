<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'shop_product_id', 'provider', 'app_id', 'player_id', 'binding_token', 'reward_amount', 'expires_at'])]
class RewardedTaskSession extends Model
{
    use HasUuids;

    protected $hidden = ['binding_token'];

    protected function casts(): array
    {
        return ['reward_amount' => 'integer', 'expires_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(RewardedTaskConversion::class);
    }
}
