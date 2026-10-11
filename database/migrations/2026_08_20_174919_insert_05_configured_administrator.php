<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->insert([
            'id' => (string) Str::uuid7(),
            'role' => config('AdminAccountConfig.ADMIN_ROLE'),
            'user_code' => config('AdminAccountConfig.ADMIN_USER_CODE'),
            'full_name' => config('AdminAccountConfig.ADMIN_FULL_NAME'),
            'email' => config('AdminAccountConfig.ADMIN_EMAIL'),
            'phone_number' => config('AdminAccountConfig.ADMIN_PHONE_NUMBER'),
            'avatar_url' => config('AdminAccountConfig.ADMIN_AVATAR_URL'),
            'password' => Hash::make(config('AdminAccountConfig.ADMIN_PASSWORD')),
            'is_active' => config('AdminAccountConfig.ADMIN_IS_ACTIVE'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', config('AdminAccountConfig.ADMIN_ROLE'))
            ->where('user_code', config('AdminAccountConfig.ADMIN_USER_CODE'))
            ->delete();
    }
};
