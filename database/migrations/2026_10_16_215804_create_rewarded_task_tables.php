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
        Schema::table('product_discount_codes', function (Blueprint $table): void {
            $table->foreignUuid('product_ad_view_id')->nullable()->change();
        });

        Schema::create('rewarded_task_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('app_id', 100);
            $table->string('player_id', 64);
            $table->string('binding_token', 64);
            $table->unsignedInteger('reward_amount');
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'shop_product_id']);
        });

        Schema::create('rewarded_task_conversions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('rewarded_task_session_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('app_id', 100);
            $table->string('conversion_key', 64)->unique();
            $table->string('request_id', 255)->unique();
            $table->string('conversion_id', 255);
            $table->string('goal_id', 255)->nullable();
            $table->string('campaign_id', 255)->nullable();
            $table->string('offer_id', 255)->nullable();
            $table->decimal('payout', 20, 8);
            $table->decimal('provider_amount', 20, 8);
            $table->foreignUuid('product_discount_code_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rewarded_task_conversions');
        Schema::dropIfExists('rewarded_task_sessions');
    }
};
