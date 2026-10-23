<?php

namespace Database\Factories;

use App\Models\ShopCoupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopCoupon>
 */
class ShopCouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_owner_id' => User::factory()->shopOwner(),
            'code' => strtoupper(fake()->unique()->bothify('SALE####??')),
            'name' => fake()->words(3, true),
            'discount_type' => 'percent',
            'discount_value' => 10,
            'minimum_order_amount' => 0,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}
