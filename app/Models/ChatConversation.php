<?php

namespace App\Models;

use Database\Factories\ChatConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable(['buyer_id', 'shop_owner_id', 'slug', 'buyer_read_at', 'shop_owner_read_at'])]
class ChatConversation extends Model
{
    /** @use HasFactory<ChatConversationFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'buyer_read_at' => 'datetime',
            'shop_owner_read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $conversation): void {
            if (filled($conversation->slug)) {
                return;
            }

            $buyer = User::query()->findOrFail($conversation->buyer_id);
            $shop = User::query()->findOrFail($conversation->shop_owner_id);
            $shopName = $shop->shop_name ?: $shop->full_name;
            $baseSlug = Str::slug($shopName.'-va-'.$buyer->full_name) ?: 'cuoc-tro-chuyen';
            $slug = $baseSlug;
            $suffix = 2;

            while (self::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix;
                $suffix++;
            }

            $conversation->slug = $slug;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function includes(User $user): bool
    {
        return $this->buyer_id === $user->getKey()
            || $this->shop_owner_id === $user->getKey();
    }
}
