<?php

namespace Database\Factories;

use App\Models\ContentType;
use App\Models\PortfolioProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioProject>
 */
class PortfolioProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'editor_id' => User::factory()->editor(),
            'content_type_id' => ContentType::factory(),
            'title' => fake()->sentence(4),
            'sample_type' => 'external',
            'external_url' => 'https://example.test/'.fake()->uuid(),
        ];
    }
}
