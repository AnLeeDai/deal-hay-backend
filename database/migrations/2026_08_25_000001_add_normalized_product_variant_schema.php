<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->string('sku_normalized', 80)->nullable()->after('sku');
            $table->boolean('has_variants')->default(false)->after('stock');
        });

        Schema::create('shop_product_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('name_normalized', 80);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(
                ['shop_product_id', 'name_normalized'],
                'product_option_name_unique',
            );
            $table->index(['shop_product_id', 'position']);
        });

        Schema::create('shop_product_option_values', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('shop_product_option_id')->constrained()->cascadeOnDelete();
            $table->string('value', 80);
            $table->string('value_normalized', 80);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(
                ['shop_product_option_id', 'value_normalized'],
                'product_option_value_unique',
            );
            $table->index(
                ['shop_product_option_id', 'position'],
                'product_option_value_position_index',
            );
        });

        Schema::table('shop_product_variants', function (Blueprint $table): void {
            $table->foreignUuid('shop_owner_id')
                ->nullable()
                ->after('shop_product_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('sku_normalized', 80)->nullable()->after('sku');
            $table->char('combination_key', 64)->nullable()->after('sku_normalized');
            $table->json('option_values_snapshot')->nullable()->after('combination_key');
            $table->unsignedInteger('position')->default(0)->after('stock');
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->timestamp('retired_at')->nullable()->after('is_default');
        });

        Schema::create('shop_product_variant_option_values', function (Blueprint $table): void {
            $table->foreignUuid('shop_product_variant_id')
                ->constrained('shop_product_variants', indexName: 'spvov_variant_fk')
                ->cascadeOnDelete();
            $table->foreignUuid('shop_product_option_value_id')
                ->constrained('shop_product_option_values', indexName: 'spvov_value_fk')
                ->cascadeOnDelete();

            $table->primary(
                ['shop_product_variant_id', 'shop_product_option_value_id'],
                'product_variant_option_value_primary',
            );
            $table->index(
                'shop_product_option_value_id',
                'product_variant_option_value_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_product_variant_option_values');

        Schema::table('shop_product_variants', function (Blueprint $table): void {
            $table->dropForeign(['shop_owner_id']);
            $table->dropColumn([
                'shop_owner_id',
                'sku_normalized',
                'combination_key',
                'option_values_snapshot',
                'position',
                'is_default',
                'retired_at',
            ]);
        });

        Schema::dropIfExists('shop_product_option_values');
        Schema::dropIfExists('shop_product_options');

        Schema::table('shop_products', function (Blueprint $table): void {
            $table->dropColumn(['sku_normalized', 'has_variants']);
        });
    }
};
