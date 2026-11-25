<?php

namespace Database\Factories;

use App\Models\Deliverable;
use App\Models\DeliverableRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliverableRevision>
 */
class DeliverableRevisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'deliverable_id' => Deliverable::factory()->submitted(),
            'requested_by_user_id' => fn (array $attributes) => Deliverable::findOrFail($attributes['deliverable_id'])->workItem->creator_id,
            'reason' => fake()->sentence(4),
            'requested_at' => now(),
        ];
    }
}
