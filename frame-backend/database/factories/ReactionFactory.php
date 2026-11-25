<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reaction>
 */
class ReactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'reaction_type' => 'like',
        ];
    }

    public function forComment(): static
    {
        return $this->state(['post_id' => null, 'comment_id' => Comment::factory()]);
    }
}
