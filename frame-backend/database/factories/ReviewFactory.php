<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'work_item_id' => WorkItem::factory()->completed(),
            'reviewer_user_id' => fn (array $attributes) => WorkItem::findOrFail($attributes['work_item_id'])->creator_id,
            'reviewed_user_id' => fn (array $attributes) => WorkItem::findOrFail($attributes['work_item_id'])->editor_id,
            'rating' => 5,
            'comment' => fake()->sentence(),
        ];
    }
}
