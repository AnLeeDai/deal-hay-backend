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
        Schema::create('shop_shipping_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('base_fee')->default(0);
            $table->json('ward_rates');
            $table->timestamps();
        });

        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->unsignedInteger('shipping_discount_amount')->default(0);
            $table->unsignedInteger('shipping_total')->default(0);
            $table->unsignedInteger('shipping_province_code')->nullable();
            $table->unsignedInteger('shipping_ward_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->dropColumn(['shipping_fee', 'shipping_discount_amount', 'shipping_total', 'shipping_province_code', 'shipping_ward_code']);
        });

        Schema::dropIfExists('shop_shipping_settings');
    }
};
