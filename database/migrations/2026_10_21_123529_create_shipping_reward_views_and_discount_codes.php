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
        Schema::create('shipping_discount_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignUuid('shop_order_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'shop_product_id']);
        });

        Schema::create('shipping_reward_views', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignUuid('shipping_discount_code_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'shop_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_reward_views');
        Schema::dropIfExists('shipping_discount_codes');
    }
};
