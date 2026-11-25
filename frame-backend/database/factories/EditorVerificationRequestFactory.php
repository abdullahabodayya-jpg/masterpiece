<?php

namespace Database\Factories;

use App\Models\EditorVerificationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorVerificationRequest>
 */
class EditorVerificationRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'editor_id' => User::factory()->editor(),
        ];
    }
}
