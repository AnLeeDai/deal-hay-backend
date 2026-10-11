<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staged_image_uploads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('path')->unique();
            $table->unsignedBigInteger('expected_size');
            $table->timestamp('expires_at');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->string('destination_path')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'expires_at'], 'staged_upload_user_expiry_index');
            $table->index(['expires_at', 'consumed_at'], 'staged_upload_prune_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staged_image_uploads');
    }
};
