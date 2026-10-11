<?php

namespace App\Models;

use App\Enums\ShopReactionTypeEnum;
use Database\Factories\ShopReactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'shop_owner_id', 'reaction'])]
class ShopReaction extends Model
{
    /** @use HasFactory<ShopReactionFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'reaction' => ShopReactionTypeEnum::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }
}
