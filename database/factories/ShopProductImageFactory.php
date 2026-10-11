<?php

namespace Database\Factories;

use App\Models\ShopProduct;
use App\Models\ShopProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProductImage>
 */
class ShopProductImageFactory extends Factory
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
            'image_url' => 'test-products/gallery/'.fake()->uuid().'.webp',
            'display_order' => fake()->numberBetween(1, 50),
        ];
    }
}
