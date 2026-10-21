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
        Schema::table('shipping_reward_views', function (Blueprint $table) {
            $table->string('reward_provider', 32)->nullable();
            $table->string('rewarded_ad_unit_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_reward_views', function (Blueprint $table) {
            $table->dropColumn(['reward_provider', 'rewarded_ad_unit_path']);
        });
    }
};
