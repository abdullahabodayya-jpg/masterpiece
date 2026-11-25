<?php

namespace Database\Factories;

use App\Enums\WorkStatus;
use App\Enums\WorkType;
use App\Models\ContentType;
use App\Models\EditorService;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkItem>
 */
class WorkItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creator_id' => User::factory()->creator(),
            'content_type_id' => ContentType::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'budget_min' => '20.00',
            'budget_max' => '45.00',
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => WorkStatus::Open]);
    }

    public function assigned(): static
    {
        return $this->state([
            'editor_id' => User::factory()->editor(),
            'status' => WorkStatus::InProgress,
            'agreed_price' => '35.00',
            'agreed_at' => now(),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->assigned()->state(['status' => WorkStatus::Completed, 'completed_at' => now()]);
    }

    public function forService(EditorService $service): static
    {
        return $this->state([
            'type' => WorkType::ServiceRequest,
            'editor_service_id' => $service->getKey(),
            'editor_id' => $service->editor_id,
            'content_type_id' => $service->content_type_id ?? ContentType::factory(),
            'currency' => $service->currency,
        ]);
    }
}
