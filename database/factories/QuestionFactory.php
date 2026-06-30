<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'author_name' => fake()->optional()->name(),
            'author_token' => Str::random(32),
            'body' => fake()->sentence(),
            'status' => 'published',
            'upvotes_count' => 0,
            'pinned' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
