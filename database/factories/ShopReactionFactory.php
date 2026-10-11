<?php

namespace Database\Factories;

use App\Enums\ShopReactionTypeEnum;
use App\Models\ShopReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopReaction>
 */
class ShopReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->customer(),
            'shop_owner_id' => User::factory()->shopOwner(),
            'reaction' => fake()->randomElement(ShopReactionTypeEnum::cases()),
        ];
    }
}
