<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'work_item_id' => WorkItem::factory()->open(),
            'editor_id' => User::factory()->editor(),
            'cover_message' => fake()->paragraph(),
            'proposed_price' => '30.00',
            'estimated_delivery_days' => 3,
        ];
    }
}
