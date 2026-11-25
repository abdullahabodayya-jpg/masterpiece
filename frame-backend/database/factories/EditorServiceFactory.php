<?php

namespace Database\Factories;

use App\Models\ContentType;
use App\Models\EditorService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorService>
 */
class EditorServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'editor_id' => User::factory()->editor(),
            'content_type_id' => ContentType::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'price' => '35.00',
            'delivery_days' => 3,
        ];
    }
}
