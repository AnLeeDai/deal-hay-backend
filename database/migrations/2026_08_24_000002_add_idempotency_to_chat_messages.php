<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chat_messages', 'client_message_id')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->uuid('client_message_id')->nullable()->after('sender_id');
            });
        }

        if (! Schema::hasColumn('chat_messages', 'request_fingerprint')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->char('request_fingerprint', 64)->nullable()->after('client_message_id');
            });
        }

        if (! $this->hasIndex('chat_messages', 'chat_sender_client_message_unique')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->unique(
                    ['chat_conversation_id', 'sender_id', 'client_message_id'],
                    'chat_sender_client_message_unique',
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('chat_messages', 'chat_sender_client_message_unique')) {
            Schema::table('chat_messages', function (Blueprint $table): void {
                $table->dropUnique('chat_sender_client_message_unique');
            });
        }

        foreach (['client_message_id', 'request_fingerprint'] as $column) {
            if (Schema::hasColumn('chat_messages', $column)) {
                Schema::table('chat_messages', function (Blueprint $table) use ($column): void {
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
