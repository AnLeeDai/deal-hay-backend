<?php

namespace Database\Factories;

use App\Models\ShopOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopOrder>
 */
class ShopOrderFactory extends Factory
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
            'shop_coupon_id' => null,
            'order_number' => fake()->unique()->bothify('ORD-########'),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('09########'),
            'shipping_address' => fake()->address(),
            'status' => 'pending',
            'subtotal' => 100_000,
            'discount_amount' => 0,
            'total' => 100_000,
            'note' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
