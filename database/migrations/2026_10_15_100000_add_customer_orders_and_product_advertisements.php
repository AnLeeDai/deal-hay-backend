<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->string('video_url', 2048)->nullable();
            $table->string('advertisement_video_url', 2048)->nullable();
            $table->unsignedSmallInteger('advertisement_duration_seconds')->nullable();
        });
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->foreignUuid('customer_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('product_ad_views', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shop_product_id')->constrained()->cascadeOnDelete();
            $table->string('video_url', 2048);
            $table->unsignedSmallInteger('duration_seconds');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at');
            $table->foreignUuid('shop_order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ad_views');
        Schema::table('shop_orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::table('shop_products', fn (Blueprint $table) => $table->dropColumn(['video_url', 'advertisement_video_url', 'advertisement_duration_seconds']));
    }
};
