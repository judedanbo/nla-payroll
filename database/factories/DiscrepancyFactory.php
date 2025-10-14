<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discrepancy>
 */
class DiscrepancyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            'ghost_employee',
            'duplicate_bank_account',
            'station_mismatch',
            'salary_anomaly',
            'missing_data',
        ]);

        return [
            'staff_id' => Staff::factory(),
            'discrepancy_type' => $type,
            'severity' => fake()->randomElement(['low', 'medium', 'medium', 'high', 'critical']),
            'description' => $this->getDescriptionForType($type),
            'status' => fake()->randomElement(['open', 'open', 'under_review', 'resolved']),
            'detected_by' => User::factory(),
            'detected_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    /**
     * Get realistic description based on discrepancy type.
     */
    protected function getDescriptionForType(string $type): string
    {
        return match ($type) {
            'ghost_employee' => fake()->randomElement([
                'Staff member not found at assigned station during verification',
                'No colleagues recognize this employee',
                'Position does not exist at this location',
                'Employee has not been seen in over 6 months',
            ]),
            'duplicate_bank_account' => fake()->randomElement([
                'Same bank account number used by multiple staff members',
                'Account number appears in 3 different staff records',
                'Duplicate account detected across departments',
            ]),
            'station_mismatch' => fake()->randomElement([
                'Staff member is working at different station than recorded',
                'Payroll indicates Accra office but staff is at Kumasi',
                'Assignment does not match physical location',
            ]),
            'salary_anomaly' => fake()->randomElement([
                'Salary significantly above grade maximum',
                'Unexplained salary increase of 40% in one month',
                'Salary below minimum for job grade',
            ]),
            'missing_data' => fake()->randomElement([
                'National ID number is missing',
                'No bank account details on file',
                'Missing employment contract documentation',
            ]),
            default => fake()->sentence(),
        };
    }

    /**
     * Indicate that the discrepancy is a ghost employee.
     */
    public function ghostEmployee(): static
    {
        return $this->state(fn (array $attributes) => [
            'discrepancy_type' => 'ghost_employee',
            'severity' => 'critical',
            'description' => 'Staff member not found at assigned station during physical verification. Multiple colleagues confirm no knowledge of this person.',
        ]);
    }

    /**
     * Indicate that the discrepancy is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    /**
     * Indicate that the discrepancy is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
        ]);
    }

    /**
     * Indicate that the discrepancy is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }
}
