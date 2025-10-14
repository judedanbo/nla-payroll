<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Staff;
use App\Models\Station;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffAssignment>
 */
class StaffAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $department = Department::inRandomOrder()->first() ?? Department::factory()->create();
        $unit = Unit::where('department_id', $department->id)->inRandomOrder()->first() ?? Unit::factory()->create(['department_id' => $department->id]);

        return [
            'staff_id' => Staff::factory(),
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'station_id' => Station::factory(),
            'assignment_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'end_date' => null,
            'is_current' => true,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the assignment is current.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
            'end_date' => null,
        ]);
    }

    /**
     * Indicate that the assignment has ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => false,
            'end_date' => fake()->dateTimeBetween($attributes['assignment_date'], 'now'),
        ]);
    }
}
