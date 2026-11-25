<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Support\ArchivedPivot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationMessage>
 */
class ConversationMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => fn (array $attributes) => Conversation::findOrFail($attributes['conversation_id'])->created_by_user_id,
            'message_body' => fake()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (ConversationMessage $message): void {
            ArchivedPivot::attachOrRestore($message->conversation->participants(), $message->sender_id);
        });
    }
}
