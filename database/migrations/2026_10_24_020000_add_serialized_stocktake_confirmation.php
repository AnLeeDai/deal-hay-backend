<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_stocktakes', function (Blueprint $table): void {
            $table->string('counting_mode', 20)->default('legacy');
            $table->unsignedBigInteger('revision')->default(0);
            $table->timestamp('saved_at')->nullable();
            $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('confirmation_hash', 64)->nullable();
            $table->json('financial_summary')->nullable();
        });
        Schema::table('shop_stocktake_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('unit_price')->nullable();
        });
        Schema::create('shop_stocktake_units', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_stocktake_id')->constrained('shop_stocktakes')->cascadeOnDelete();
            $table->foreignUuid('shop_stocktake_item_id')->constrained('shop_stocktake_items')->cascadeOnDelete();
            // The SKU and UUID remain in the report even after catalog deletion.
            $table->uuid('inventory_unit_id');
            $table->string('sku', 100);
            $table->string('code', 100);
            $table->boolean('expected')->default(true);
            $table->boolean('counted')->default(false);
            $table->timestamps();
            $table->unique(['shop_stocktake_id', 'inventory_unit_id'], 'stocktake_serial_unique');
            $table->index(['shop_stocktake_item_id', 'sku'], 'stocktake_unit_item_sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_stocktake_units');
        Schema::table('shop_stocktake_items', fn (Blueprint $table) => $table->dropColumn('unit_price'));
        Schema::table('shop_stocktakes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['counting_mode', 'revision', 'saved_at', 'confirmed_at', 'confirmation_hash', 'financial_summary']);
        });
    }
};
