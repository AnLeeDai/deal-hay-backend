<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_inventory_units', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->nullable()->constrained('shop_products')->nullOnDelete();
            $table->foreignUuid('shop_product_variant_id')->nullable()->constrained('shop_product_variants')->nullOnDelete();
            $table->string('sku', 40)->unique();
            $table->enum('status', ['available', 'removed'])->default('available');
            $table->timestamp('removed_at')->nullable();
            $table->string('removed_reference_type')->nullable();
            $table->uuid('removed_reference_id')->nullable();
            $table->timestamps();
            $table->index(['shop_owner_id', 'status', 'id'], 'inventory_unit_owner_status');
            $table->index(['shop_product_id', 'shop_product_variant_id', 'status'], 'inventory_unit_target_status');
            $table->index(['removed_reference_type', 'removed_reference_id'], 'inventory_unit_removed_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_inventory_units');
    }
};
