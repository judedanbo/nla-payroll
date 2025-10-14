<?php

namespace Database\Factories;

use App\Models\HeadcountVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationNote>
 */
class VerificationNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'headcount_verification_id' => HeadcountVerification::factory(),
            'created_by' => User::factory(),
            'note_content' => fake()->paragraph(),
            'note_type' => fake()->randomElement(['general', 'discrepancy', 'concern', 'clarification']),
        ];
    }

    /**
     * Indicate that this is a discrepancy note.
     */
    public function discrepancy(): static
    {
        return $this->state(fn (array $attributes) => [
            'note_type' => 'discrepancy',
            'note_content' => fake()->randomElement([
                'Staff details do not match payroll records.',
                'Job title mismatch between system and actual position.',
                'Department assignment does not match physical location.',
                'Salary amount inconsistent with job grade.',
            ]),
        ]);
    }

    /**
     * Indicate that this is a concern note.
     */
    public function concern(): static
    {
        return $this->state(fn (array $attributes) => [
            'note_type' => 'concern',
            'note_content' => fake()->randomElement([
                'Staff appeared uncertain about employment details.',
                'No physical presence observed during multiple verification attempts.',
                'Colleagues unfamiliar with staff member.',
                'Incomplete documentation provided.',
            ]),
        ]);
    }
}
