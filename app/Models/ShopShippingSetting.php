<?php

namespace App\Models;

use Database\Factories\ShopShippingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shop_owner_id', 'enabled', 'base_fee', 'ward_rates'])]
class ShopShippingSetting extends Model
{
    /** @use HasFactory<ShopShippingSettingFactory> */
    use HasFactory, HasUuids;

    protected $attributes = ['enabled' => false, 'base_fee' => 0, 'ward_rates' => '[]'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'base_fee' => 'integer',
            'ward_rates' => 'array',
        ];
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }
}
