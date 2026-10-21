<?php

namespace Database\Factories;

use App\Models\ShippingRewardView;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRewardView>
 */
class ShippingRewardViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'shop_product_id' => ShopProduct::factory(),
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
