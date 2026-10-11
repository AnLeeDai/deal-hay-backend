<?php

namespace Database\Factories;

use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProductVariant>
 */
class ShopProductVariantFactory extends Factory
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
            'shop_owner_id' => null,
            'sku' => fake()->unique()->bothify('VAR-########-????'),
            'sku_normalized' => null,
            'combination_key' => null,
            'option_values_snapshot' => null,
            'color' => fake()->randomElement(['Đen', 'Trắng', 'Xanh']),
            'size' => fake()->randomElement(['S', 'M', 'L']),
            'price' => fake()->numberBetween(10_000, 500_000),
            'stock' => fake()->numberBetween(0, 200),
            'position' => 0,
            'is_active' => true,
            'is_default' => false,
            'retired_at' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false, 'is_default' => false]);
    }
}
