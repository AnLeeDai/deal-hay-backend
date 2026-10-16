<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rewarded_task_session_id', 'provider', 'app_id', 'conversion_key', 'request_id', 'conversion_id', 'goal_id', 'campaign_id', 'offer_id', 'payout', 'provider_amount', 'product_discount_code_id'])]
class RewardedTaskConversion extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return ['payout' => 'decimal:8', 'provider_amount' => 'decimal:8'];
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(ProductDiscountCode::class, 'product_discount_code_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RewardedTaskSession::class, 'rewarded_task_session_id');
    }
}
