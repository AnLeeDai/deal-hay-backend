<?php

namespace App\Models;

use Database\Factories\ChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'chat_conversation_id',
    'sender_id',
    'client_message_id',
    'request_fingerprint',
    'shop_product_id',
    'shop_product_variant_id',
    'body',
    'read_at',
    'attachment_disk',
    'attachment_path',
    'attachment_name',
    'attachment_mime',
    'attachment_size',
])]
#[Hidden(['request_fingerprint', 'attachment_public_cleanup_pending'])]
class ChatMessage extends Model
{
    /** @use HasFactory<ChatMessageFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'attachment_public_cleanup_pending' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function shopProduct(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class);
    }

    public function shopProductVariant(): BelongsTo
    {
        return $this->belongsTo(ShopProductVariant::class);
    }
}
