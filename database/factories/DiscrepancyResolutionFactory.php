<?php

namespace Database\Factories;

use App\Models\Discrepancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiscrepancyResolution>
 */
class DiscrepancyResolutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $resolutionType = fake()->randomElement([
            'corrected',
            'verified_valid',
            'data_updated',
            'no_action_required',
        ]);

        return [
            'discrepancy_id' => Discrepancy::factory(),
            'resolved_by' => User::factory(),
            'resolved_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'resolution_type' => $resolutionType,
            'resolution_notes' => $this->getNotesForType($resolutionType),
            'outcome' => fake()->randomElement(['resolved', 'resolved', 'resolved', 'partially_resolved']),
        ];
    }

    /**
     * Get realistic notes based on resolution type.
     */
    protected function getNotesForType(string $type): string
    {
        return match ($type) {
            'corrected' => fake()->randomElement([
                'Bank account details have been corrected in the system',
                'Station assignment updated to reflect actual location',
                'Salary adjusted to match job grade requirements',
            ]),
            'verified_valid' => fake()->randomElement([
                'Verified with department head - employee is legitimate',
                'Documentation reviewed and found to be accurate',
                'Anomaly explained by approved overtime work',
            ]),
            'staff_removed' => fake()->randomElement([
                'Staff member confirmed as ghost employee and removed from payroll',
                'Duplicate record identified and deleted',
                'Terminated employee removed from active roster',
            ]),
            'data_updated' => fake()->randomElement([
                'Missing national ID number added to record',
                'Bank details updated with verified information',
                'Employment contract scanned and uploaded',
            ]),
            'no_action_required' => fake()->randomElement([
                'Discrepancy was due to timing of data updates',
                'Explained by legitimate business process',
                'Falls within acceptable variance',
            ]),
            'escalated' => fake()->randomElement([
                'Case escalated to senior management for review',
                'Referred to legal department',
                'Requires board approval for resolution',
            ]),
            default => fake()->sentence(),
        };
    }

    /**
     * Indicate that the resolution was successful.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'outcome' => 'resolved',
        ]);
    }

    /**
     * Indicate that the issue was escalated.
     */
    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolution_type' => 'escalated',
            'outcome' => 'partially_resolved',
            'resolution_notes' => 'Issue requires senior management review due to complexity and potential legal implications.',
        ]);
    }
}
