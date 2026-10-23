<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_stocktakes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('status', 20)->default('counting');
            $table->boolean('stock_adjusted')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['shop_owner_id', 'created_at']);
        });

        Schema::create('shop_stocktake_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_stocktake_id')->constrained('shop_stocktakes')->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->nullable()->constrained('shop_products')->nullOnDelete();
            $table->foreignUuid('shop_product_variant_id')->nullable()->constrained('shop_product_variants')->nullOnDelete();
            $table->string('code', 255);
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('expected_quantity')->default(0);
            $table->unsignedBigInteger('expected_movement_count')->default(0);
            $table->unsignedInteger('counted_quantity')->default(0);
            $table->boolean('is_unknown')->default(false);
            $table->timestamps();
            $table->unique(['shop_stocktake_id', 'code'], 'stocktake_item_code_unique');
        });

        Schema::create('shop_stocktake_scans', function (Blueprint $table): void {
            $table->foreignUuid('shop_stocktake_id')->constrained('shop_stocktakes')->cascadeOnDelete();
            $table->uuid('scan_id');
            $table->string('code', 255);
            $table->unsignedInteger('quantity');
            $table->primary(['shop_stocktake_id', 'scan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_stocktake_scans');
        Schema::dropIfExists('shop_stocktake_items');
        Schema::dropIfExists('shop_stocktakes');
    }
};
