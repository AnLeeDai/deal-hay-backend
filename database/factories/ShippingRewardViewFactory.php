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
            'reward_provider' => 'google_ad_manager',
            'rewarded_ad_unit_path' => config('shipping_rewards.google_ad_unit_path'),
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
