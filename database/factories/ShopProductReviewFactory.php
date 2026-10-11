<?php

namespace Database\Factories;

use App\Enums\ShopReactionTypeEnum;
use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProductReview>
 */
class ShopProductReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_product_id' => ShopProduct::factory(),
            'customer_id' => User::factory()->customer(),
            'reaction' => fake()->randomElement(ShopReactionTypeEnum::cases()),
            'content' => fake()->sentence(),
            'image_url' => null,
            'shop_reply' => null,
            'replied_at' => null,
        ];
    }
}
