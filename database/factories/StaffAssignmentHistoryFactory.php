<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Staff;
use App\Models\Station;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StaffAssignmentHistory>
 */
class StaffAssignmentHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $oldDepartment = Department::inRandomOrder()->first() ?? Department::factory()->create();
        $newDepartment = Department::inRandomOrder()->first() ?? Department::factory()->create();

        $oldUnit = Unit::where('department_id', $oldDepartment->id)->inRandomOrder()->first() ?? Unit::factory()->create(['department_id' => $oldDepartment->id]);
        $newUnit = Unit::where('department_id', $newDepartment->id)->inRandomOrder()->first() ?? Unit::factory()->create(['department_id' => $newDepartment->id]);

        return [
            'staff_id' => Staff::factory(),
            'changed_by' => User::factory(),
            'old_department_id' => $oldDepartment->id,
            'old_unit_id' => $oldUnit->id,
            'old_station_id' => Station::factory(),
            'new_department_id' => $newDepartment->id,
            'new_unit_id' => $newUnit->id,
            'new_station_id' => Station::factory(),
            'changed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'reason' => fake()->optional(0.7)->randomElement([
                'Promotion',
                'Transfer',
                'Departmental restructuring',
                'Performance-based reassignment',
                'Employee request',
            ]),
        ];
    }

    /**
     * Indicate that the change is a promotion.
     */
    public function promotion(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'Promotion',
        ]);
    }

    /**
     * Indicate that the change is a transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'Transfer',
        ]);
    }
}
