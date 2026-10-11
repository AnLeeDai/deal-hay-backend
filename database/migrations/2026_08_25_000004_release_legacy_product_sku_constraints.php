<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('shop_products', 'shop_products_sku_unique')) {
            Schema::table('shop_products', function (Blueprint $table): void {
                $table->dropUnique('shop_products_sku_unique');
            });
        }

        if (Schema::hasIndex('shop_product_variants', 'shop_product_variants_shop_product_id_sku_unique')) {
            Schema::table('shop_product_variants', function (Blueprint $table): void {
                $table->dropUnique('shop_product_variants_shop_product_id_sku_unique');
            });
        }

        DB::table('shop_products')
            ->select(['id', 'sku_normalized'])
            ->whereNotNull('sku_normalized')
            ->orderBy('id')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    DB::table('shop_products')
                        ->where('id', $product->id)
                        ->update(['sku' => $product->sku_normalized]);
                }
            });
        DB::table('shop_product_variants')
            ->select(['id', 'sku_normalized'])
            ->whereNotNull('sku_normalized')
            ->orderBy('id')
            ->chunkById(200, function ($variants): void {
                foreach ($variants as $variant) {
                    DB::table('shop_product_variants')
                        ->where('id', $variant->id)
                        ->update(['sku' => $variant->sku_normalized]);
                }
            });
    }

    public function down(): void
    {
        // Legacy raw-SKU indexes cannot be restored safely after shops reuse the same SKU.
    }
};
