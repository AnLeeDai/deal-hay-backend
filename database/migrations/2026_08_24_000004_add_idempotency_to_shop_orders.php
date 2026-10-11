<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('shop_orders', 'idempotency_key')) {
            Schema::table('shop_orders', function (Blueprint $table): void {
                $table->uuid('idempotency_key')->nullable()->after('shop_owner_id');
            });
        }

        if (! Schema::hasColumn('shop_orders', 'request_fingerprint')) {
            Schema::table('shop_orders', function (Blueprint $table): void {
                $table->char('request_fingerprint', 64)->nullable()->after('idempotency_key');
            });
        }

        if (! $this->hasIndex('shop_orders', 'shop_owner_idempotency_unique')) {
            Schema::table('shop_orders', function (Blueprint $table): void {
                $table->unique(
                    ['shop_owner_id', 'idempotency_key'],
                    'shop_owner_idempotency_unique',
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('shop_orders', 'shop_owner_idempotency_unique')) {
            Schema::table('shop_orders', function (Blueprint $table): void {
                $table->dropUnique('shop_owner_idempotency_unique');
            });
        }

        foreach (['idempotency_key', 'request_fingerprint'] as $column) {
            if (Schema::hasColumn('shop_orders', $column)) {
                Schema::table('shop_orders', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }
};
