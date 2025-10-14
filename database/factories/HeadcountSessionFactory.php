<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeadcountSession>
 */
class HeadcountSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', '+1 month');

        return [
            'session_name' => fake()->randomElement([
                'Q1 2025 Headcount Verification',
                'Q2 2025 Headcount Verification',
                'Annual Staff Audit 2025',
                'Mid-Year Verification 2025',
                'Regional Office Verification',
            ]),
            'description' => fake()->optional(0.7)->sentence(),
            'start_date' => $startDate,
            'end_date' => null,
            'status' => fake()->randomElement(['planned', 'in_progress', 'completed']),
            'created_by' => User::factory(),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the session is planned.
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => null,
        ]);
    }

    /**
     * Indicate that the session is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_date' => null,
        ]);
    }

    /**
     * Indicate that the session is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = fake()->dateTimeBetween('-6 months', '-1 month');
            $endDate = fake()->dateTimeBetween($startDate, 'now');

            return [
                'status' => 'completed',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }
}
