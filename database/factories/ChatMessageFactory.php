<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ChatMessage> */
class ChatMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'chat_conversation_id' => ChatConversation::factory(),
            'sender_id' => fn (array $attributes) => ChatConversation::query()
                ->findOrFail($attributes['chat_conversation_id'])
                ->buyer_id,
            'body' => fake()->sentence(),
            'read_at' => null,
        ];
    }
}
