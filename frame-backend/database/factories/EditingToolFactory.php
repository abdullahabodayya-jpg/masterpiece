<?php

namespace Database\Factories;

use App\Models\EditingTool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditingTool>
 */
class EditingToolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
        ];
    }
}
