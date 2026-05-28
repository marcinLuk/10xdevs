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
        $type = $this->faker->randomElement(['watering', 'fertilizing', 'planting', null]);

        $descriptions = [
            'watering' => [
                'Watered the tomatoes and cucumbers in the greenhouse.',
                'Deep watered the raised beds — soil was very dry after the heat.',
                'Gave the herb garden a good soaking this morning.',
                'Watered newly transplanted seedlings twice today.',
                'Irrigated the berry bushes along the fence.',
            ],
            'fertilizing' => [
                'Applied slow-release granular fertilizer to the vegetable beds.',
                'Fed the roses with liquid fertilizer concentrate.',
                'Spread compost around the base of the fruit trees.',
                'Used fish emulsion on the pepper and eggplant beds.',
                'Top-dressed the lawn with organic fertilizer mix.',
            ],
            'planting' => [
                'Planted 6 tomato seedlings in the raised bed.',
                'Sowed carrot and radish seeds in rows.',
                'Transplanted lavender cuttings to the herb border.',
                'Put in a row of sunflowers along the south fence.',
                'Planted basil, parsley, and dill in the kitchen garden.',
            ],
            null => [
                'Pruned the overgrown rose bushes near the gate.',
                'Weeded the entire vegetable patch — took about two hours.',
                'Harvested zucchini, beans, and a handful of cherry tomatoes.',
                'Turned the compost pile and added fresh kitchen scraps.',
                'Removed dead flower heads to encourage new blooms.',
            ],
        ];

        $pool = $descriptions[$type] ?? $descriptions[null];

        return [
            'user_id' => User::factory(),
            'description' => $this->faker->randomElement($pool),
            'task_date' => $this->faker->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'type' => $type,
        ];
    }
}
