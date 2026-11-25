<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Connection>
 */
class ConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'recipient_id' => User::factory(),
        ];
    }
}
