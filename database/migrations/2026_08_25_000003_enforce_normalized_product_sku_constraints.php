<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('shop_products', 'shop_product_owner_sku_unique')) {
            Schema::table('shop_products', function (Blueprint $table): void {
                $table->unique(
                    ['shop_owner_id', 'sku_normalized'],
                    'shop_product_owner_sku_unique',
                );
            });
        }

        if (! Schema::hasIndex('shop_product_variants', 'shop_variant_owner_sku_unique')) {
            Schema::table('shop_product_variants', function (Blueprint $table): void {
                $table->unique(
                    ['shop_owner_id', 'sku_normalized'],
                    'shop_variant_owner_sku_unique',
                );
            });
        }

        if (! Schema::hasIndex('shop_product_variants', 'shop_variant_combination_unique')) {
            Schema::table('shop_product_variants', function (Blueprint $table): void {
                $table->unique(
                    ['shop_product_id', 'combination_key'],
                    'shop_variant_combination_unique',
                );
            });
        }

        if (! Schema::hasIndex('shop_product_variants', 'shop_variant_catalog_index')) {
            Schema::table('shop_product_variants', function (Blueprint $table): void {
                $table->index(
                    ['shop_product_id', 'retired_at', 'is_active', 'position'],
                    'shop_variant_catalog_index',
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('shop_product_variants', 'shop_variant_catalog_index')) {
            Schema::table('shop_product_variants', function (Blueprint $table): void {
                $table->dropIndex('shop_variant_catalog_index');
            });
        }

        if (Schema::hasIndex('shop_product_variants', 'shop_variant_combination_unique')) {
            Schema::table('shop_product_variants', function (Blueprint $table): void {
                $table->dropUnique('shop_variant_combination_unique');
            });
        }

        if (Schema::hasIndex('shop_product_variants', 'shop_variant_owner_sku_unique')) {
            Schema::table('shop_product_variants', function (Blueprint $table): void {
                $table->dropUnique('shop_variant_owner_sku_unique');
            });
        }

        if (Schema::hasIndex('shop_products', 'shop_product_owner_sku_unique')) {
            Schema::table('shop_products', function (Blueprint $table): void {
                $table->dropUnique('shop_product_owner_sku_unique');
            });
        }
    }
};
