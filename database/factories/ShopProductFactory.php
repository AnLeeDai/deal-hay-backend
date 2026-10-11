<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShopProduct> */
class ShopProductFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'shop_owner_id' => User::factory()->shopOwner(),
            'product_category_id' => ProductCategory::factory(),
            'sku' => fake()->unique()->bothify('SP-########-????'),
            'name' => fake()->words(4, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(10_000, 500_000),
            'old_price' => null,
            'stock' => fake()->numberBetween(1, 200),
            'has_variants' => false,
            'label' => fake()->optional()->randomElement(['Nổi bật', 'Chính hãng', 'Hàng mới']),
            'image_url' => 'test-products/product.webp',
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function hasVariants(): static
    {
        return $this->state(fn (): array => ['has_variants' => true]);
    }
}
