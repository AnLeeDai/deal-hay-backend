<?php

namespace Database\Factories;

use App\Models\ShopFollow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopFollow>
 */
class ShopFollowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'follower_id' => User::factory()->customer(),
            'shop_owner_id' => User::factory()->shopOwner(),
        ];
    }
}
