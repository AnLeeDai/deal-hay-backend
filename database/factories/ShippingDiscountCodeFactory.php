<?php

namespace Database\Factories;

use App\Models\ShippingDiscountCode;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShippingDiscountCode>
 */
class ShippingDiscountCodeFactory extends Factory
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
            'code' => Str::upper(Str::random(32)),
            'expires_at' => now()->addDays(7),
        ];
    }
}
