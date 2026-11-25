<?php

namespace Database\Factories;

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deliverable>
 */
class DeliverableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'work_item_id' => WorkItem::factory()->assigned(),
            'title' => fake()->sentence(4),
        ];
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => DeliverableStatus::Submitted,
            'file_name' => 'sample.mp4',
            'storage_key' => 'synthetic/sample.mp4',
            'submitted_at' => now(),
        ]);
    }
}
