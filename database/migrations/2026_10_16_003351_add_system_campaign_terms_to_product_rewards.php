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
        Schema::table('product_ad_views', function (Blueprint $table): void {
            $table->string('provider', 80)->nullable();
            $table->string('campaign_id', 120)->nullable();
            $table->string('ad_type', 80)->nullable();
            $table->string('reward_type', 20)->nullable();
            $table->unsignedInteger('reward_value')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_reference')->nullable();
            $table->json('provider_session')->nullable();
            $table->unique(['provider', 'verification_reference'], 'product_ad_views_provider_verification_unique');
        });
        Schema::table('product_discount_codes', function (Blueprint $table): void {
            $table->string('reward_type', 20)->nullable();
            $table->unsignedInteger('reward_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_discount_codes', fn (Blueprint $table) => $table->dropColumn(['reward_type', 'reward_value']));
        Schema::table('product_ad_views', function (Blueprint $table): void {
            $table->dropUnique('product_ad_views_provider_verification_unique');
            $table->dropColumn(['provider', 'campaign_id', 'ad_type', 'reward_type', 'reward_value', 'verified_at', 'verification_reference', 'provider_session']);
        });
    }
};
