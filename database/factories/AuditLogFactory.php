<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(['created', 'updated', 'deleted', 'viewed', 'exported']);
        $auditableTypes = [
            \App\Models\Staff::class,
            \App\Models\Discrepancy::class,
            \App\Models\HeadcountVerification::class,
            \App\Models\Report::class,
        ];

        return [
            'user_id' => \App\Models\User::factory(),
            'action' => $action,
            'auditable_type' => fake()->randomElement($auditableTypes),
            'auditable_id' => fake()->numberBetween(1, 100),
            'old_values' => $action === 'updated' ? [
                'name' => fake()->name(),
                'status' => 'pending',
            ] : null,
            'new_values' => $action === 'updated' ? [
                'name' => fake()->name(),
                'status' => 'approved',
            ] : null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate the audit log is for a creation action.
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'created',
            'old_values' => null,
            'new_values' => [
                'name' => fake()->name(),
                'status' => 'active',
            ],
        ]);
    }

    /**
     * Indicate the audit log is for an update action.
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'updated',
            'old_values' => [
                'name' => fake()->name(),
                'status' => 'pending',
            ],
            'new_values' => [
                'name' => fake()->name(),
                'status' => 'approved',
            ],
        ]);
    }

    /**
     * Indicate the audit log is for a deletion action.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'deleted',
            'old_values' => [
                'name' => fake()->name(),
                'status' => 'active',
            ],
            'new_values' => null,
        ]);
    }
}
