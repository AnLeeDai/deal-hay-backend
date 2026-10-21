<?php

namespace Database\Factories;

use App\Models\ShopShippingSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopShippingSetting>
 */
class ShopShippingSettingFactory extends Factory
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
            'enabled' => false,
            'base_fee' => 0,
            'ward_rates' => [],
        ];
    }
}
