<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChatConversation> */
class ChatConversationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'buyer_id' => User::factory()->customer(),
            'shop_owner_id' => User::factory()->shopOwner(),
        ];
    }
}
