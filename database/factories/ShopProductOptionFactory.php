<?php

namespace Database\Factories;

use App\Models\ShopProduct;
use App\Models\ShopProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShopProductOption> */
class ShopProductOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_product_id' => ShopProduct::factory(),
            'name' => fake()->randomElement(['Màu sắc', 'Kích thước', 'Chất liệu']),
            'position' => 0,
        ];
    }
}
