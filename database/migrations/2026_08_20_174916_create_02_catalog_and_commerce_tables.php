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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('product_category_shop', function (Blueprint $table) {
            $table->foreignUuid('product_category_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->primary(['product_category_id', 'shop_owner_id']);
            $table->index(['shop_owner_id', 'display_order']);
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('product_category_id')->constrained()->restrictOnDelete();
            $table->string('sku', 80)->nullable()->unique();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('old_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('label', 80)->nullable();
            $table->string('image_url');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['shop_owner_id', 'slug']);
            $table->index(['shop_owner_id', 'created_at']);
            $table->index(['shop_owner_id', 'is_active', 'created_at']);
            $table->index(['shop_owner_id', 'product_category_id']);
            $table->index(['is_active', 'created_at']);
            $table->index(['is_active', 'product_category_id', 'created_at']);
        });

        Schema::create('shop_product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->string('image_url');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index(['shop_product_id', 'display_order']);
        });

        Schema::create('shop_product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 80);
            $table->string('color', 80)->nullable();
            $table->string('size', 80)->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_product_id', 'sku']);
            $table->index(['shop_product_id', 'is_active']);
            $table->index(['shop_product_id', 'created_at']);
        });

        Schema::create('shop_coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('discount_type', 20);
            $table->unsignedInteger('discount_value');
            $table->unsignedInteger('minimum_order_amount')->default(0);
            $table->unsignedInteger('maximum_discount_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_owner_id', 'code']);
            $table->index(['shop_owner_id', 'is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('shop_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shop_coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->text('shipping_address');
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total');
            $table->text('note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['shop_owner_id', 'created_at']);
            $table->index(['shop_owner_id', 'status', 'created_at']);
        });

        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('shop_product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku', 80)->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
            $table->index(['shop_order_id', 'shop_product_id']);
        });

        Schema::create('shop_inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->integer('quantity_delta');
            $table->unsignedInteger('stock_after');
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['shop_owner_id', 'created_at']);
            $table->index(
                ['shop_product_id', 'shop_product_variant_id', 'created_at'],
                'inventory_product_variant_created_index',
            );
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('shop_profile_change_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('changes');
            $table->timestamps();
            $table->index(['shop_owner_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_profile_change_logs');
        Schema::dropIfExists('shop_inventory_movements');
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_coupons');
        Schema::dropIfExists('shop_product_variants');
        Schema::dropIfExists('shop_product_images');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('product_category_shop');
        Schema::dropIfExists('product_categories');
    }
};
