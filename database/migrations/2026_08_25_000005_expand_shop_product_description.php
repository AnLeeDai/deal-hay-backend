<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table): void {
            $table->longText('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Widening is intentionally not reversed because long descriptions may already exist.
    }
};
