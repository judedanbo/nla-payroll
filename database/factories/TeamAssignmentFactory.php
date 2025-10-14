<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamAssignment>
 */
class TeamAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', 'now');
        $status = fake()->randomElement(['active', 'completed', 'cancelled']);
        $endDate = $status !== 'active' ? fake()->dateTimeBetween($startDate, 'now') : null;

        return [
            'headcount_session_id' => \App\Models\HeadcountSession::factory(),
            'user_id' => \App\Models\User::factory(),
            'station_id' => \App\Models\Station::factory(),
            'assigned_by' => \App\Models\User::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
        ];
    }

    /**
     * Indicate the assignment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'end_date' => null,
        ]);
    }

    /**
     * Indicate the assignment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'end_date' => fake()->dateTimeBetween($attributes['start_date'], 'now'),
        ]);
    }

    /**
     * Indicate the assignment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'end_date' => fake()->dateTimeBetween($attributes['start_date'], 'now'),
        ]);
    }
}
