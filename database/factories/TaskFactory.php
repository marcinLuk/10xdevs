<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
            'task_date' => $this->faker->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'type' => $this->faker->randomElement(['watering', 'fertilizing', 'planting', null]),
        ];
    }
}
