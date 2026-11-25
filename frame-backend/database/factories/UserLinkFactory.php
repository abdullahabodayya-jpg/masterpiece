<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLink>
 */
class UserLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'platform_name' => 'Example',
            'url' => 'https://example.test/'.fake()->uuid(),
        ];
    }
}
