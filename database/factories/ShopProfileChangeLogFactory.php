<?php

namespace Database\Factories;

use App\Models\ShopProfileChangeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopProfileChangeLog>
 */
class ShopProfileChangeLogFactory extends Factory
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
            'changed_by' => null,
            'changes' => ['shop_name' => ['old' => 'Tên cũ', 'new' => 'Tên mới']],
        ];
    }
}
