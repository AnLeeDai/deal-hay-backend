<?php

namespace Database\Factories;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'role' => UserRoleEnum::CUSTOMER,
            'user_code' => 'CUS-'.fake()->unique()->numerify('#####'),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => null,
            'shop_name' => null,
            'shop_email' => null,
            'shop_phone_number' => null,
            'shop_avatar_url' => null,
            'shop_province' => null,
            'shop_ward' => null,
            'shop_address_detail' => null,
            'province' => null,
            'ward' => null,
            'address_detail' => null,
            'avatar_url' => null,
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRoleEnum::CUSTOMER,
            'user_code' => 'CUS-'.fake()->unique()->numerify('#####'),
        ]);
    }

    public function shopOwner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRoleEnum::SHOP_OWNER,
            'user_code' => 'SHPOW-'.fake()->unique()->numerify('#####'),
            'shop_name' => fake()->company(),
        ]);
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRoleEnum::ADMINISTRATOR,
            'user_code' => config('AdminAccountConfig.ADMIN_USER_CODE'),
            'full_name' => config('AdminAccountConfig.ADMIN_FULL_NAME'),
            'email' => config('AdminAccountConfig.ADMIN_EMAIL'),
        ]);
    }
}
