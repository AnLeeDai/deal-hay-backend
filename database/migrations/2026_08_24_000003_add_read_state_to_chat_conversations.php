<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chat_conversations', 'buyer_read_at')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->timestamp('buyer_read_at')->nullable()->after('shop_owner_id');
            });
        }

        if (! Schema::hasColumn('chat_conversations', 'shop_owner_read_at')) {
            Schema::table('chat_conversations', function (Blueprint $table): void {
                $table->timestamp('shop_owner_read_at')->nullable()->after('buyer_read_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['buyer_read_at', 'shop_owner_read_at'] as $column) {
            if (Schema::hasColumn('chat_conversations', $column)) {
                Schema::table('chat_conversations', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
