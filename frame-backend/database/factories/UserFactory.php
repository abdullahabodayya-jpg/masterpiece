<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(['creator', 'editor']),
            'status' => 'active',
            'display_name' => fake()->userName(),
            'bio' => fake()->paragraph(),
            'remember_token' => Str::random(10),
        ];
    }

    public function creator(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'creator',
            'creator_niche' => fake()->randomElement([
                'Gaming',
                'Education',
                'Technology',
                'Lifestyle',
                'Entertainment',
            ]),
            'audience_size' => fake()->numberBetween(100, 1000000),
            'audience_size_updated_at' => now(),
        ]);
    }

    public function editor(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'editor',
            'experience_level' => fake()->randomElement([
                'junior',
                'intermediate',
                'senior',
                'expert',
            ]),
            'years_of_experience' => fake()->numberBetween(1, 15),
            'professional_title' => 'Video Editor',
            'is_open_to_work' => true,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function support(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'support',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
