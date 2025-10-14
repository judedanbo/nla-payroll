<?php

namespace Database\Factories;

use App\Models\Discrepancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiscrepancyNote>
 */
class DiscrepancyNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discrepancy_id' => Discrepancy::factory(),
            'created_by' => User::factory(),
            'note_content' => fake()->paragraph(),
            'is_internal' => fake()->boolean(30), // 30% internal notes
        ];
    }

    /**
     * Indicate that the note is internal.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => true,
            'note_content' => fake()->randomElement([
                'Flagged for management review',
                'Requires follow-up with HR department',
                'Legal implications need to be assessed',
                'Sensitive case - handle with confidentiality',
            ]),
        ]);
    }

    /**
     * Indicate that the note is public/client-facing.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => false,
            'note_content' => fake()->randomElement([
                'Additional documentation requested from staff member',
                'Verification scheduled for next week',
                'Awaiting response from department head',
                'Investigation in progress',
            ]),
        ]);
    }
}
