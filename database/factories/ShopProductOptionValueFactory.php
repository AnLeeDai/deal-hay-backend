<?php

namespace Database\Factories;

use App\Models\ShopProductOption;
use App\Models\ShopProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ShopProductOptionValue> */
class ShopProductOptionValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_product_option_id' => ShopProductOption::factory(),
            'value' => fake()->unique()->word(),
            'position' => 0,
        ];
    }
}
