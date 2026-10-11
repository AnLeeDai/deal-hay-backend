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
        if (! Schema::hasColumn('chat_messages', 'attachment_public_cleanup_pending')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->boolean('attachment_public_cleanup_pending')->default(false)->after('attachment_size');
            });
        }

        if (! $this->hasIndex('chat_messages', 'chat_attachment_cleanup_pending_index')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->index(
                    ['attachment_disk', 'attachment_public_cleanup_pending', 'id'],
                    'chat_attachment_cleanup_pending_index',
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->hasIndex('chat_messages', 'chat_attachment_cleanup_pending_index')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->dropIndex('chat_attachment_cleanup_pending_index');
            });
        }

        if (Schema::hasColumn('chat_messages', 'attachment_public_cleanup_pending')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->dropColumn('attachment_public_cleanup_pending');
            });
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => $index['name'] === $name);
    }
};
