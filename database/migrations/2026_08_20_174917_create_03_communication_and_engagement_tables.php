<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['follower_id', 'shop_owner_id']);
            $table->index(['shop_owner_id', 'created_at']);
        });

        Schema::create('shop_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 16);
            $table->timestamps();
            $table->unique(['customer_id', 'shop_owner_id']);
            $table->index(['shop_owner_id', 'reaction']);
        });

        Schema::create('shop_product_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction', 16);
            $table->text('content');
            $table->string('image_url')->nullable();
            $table->text('shop_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'shop_product_id']);
            $table->index(['shop_product_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->nullable()->unique();
            $table->foreignUuid('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['buyer_id', 'shop_owner_id']);
            $table->index(['buyer_id', 'updated_at']);
            $table->index(['shop_owner_id', 'updated_at']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chat_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('shop_product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['chat_conversation_id', 'created_at']);
            $table->index(
                ['chat_conversation_id', 'read_at', 'sender_id'],
                'chat_messages_unread_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('shop_product_reviews');
        Schema::dropIfExists('shop_reactions');
        Schema::dropIfExists('shop_follows');
    }
};
