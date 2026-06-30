<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => 'open',
            'require_approval' => false,
            'allow_anonymous' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => 'closed']);
    }

    public function moderated(): static
    {
        return $this->state(fn () => ['require_approval' => true]);
    }
}
