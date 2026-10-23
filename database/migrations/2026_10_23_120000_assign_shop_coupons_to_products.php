<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_coupon_product', function (Blueprint $table): void {
            $table->foreignUuid('shop_coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->primary(['shop_coupon_id', 'shop_product_id']);
        });

        // Existing coupons previously applied to every product in their shop.
        DB::table('shop_coupons')->orderBy('id')->chunkById(100, function ($coupons): void {
            foreach ($coupons as $coupon) {
                DB::table('shop_products')->where('shop_owner_id', $coupon->shop_owner_id)
                    ->orderBy('id')->chunkById(100, function ($products) use ($coupon): void {
                        DB::table('shop_coupon_product')->insert($products->map(fn ($product): array => [
                            'shop_coupon_id' => $coupon->id,
                            'shop_product_id' => $product->id,
                        ])->all());
                    });
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_coupon_product');
    }
};
