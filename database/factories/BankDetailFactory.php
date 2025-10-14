<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankDetail>
 */
class BankDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory(),
            'bank_id' => Bank::factory(),
            'account_number' => fake()->numerify('##########'), // 10-digit account
            'account_name' => fake()->name(),
            'account_type' => fake()->randomElement(['savings', 'current', 'checking']),
            'is_primary' => false,
            'is_active' => true,
            'activated_at' => fake()->dateTimeBetween('-5 years', 'now'),
            'deactivated_at' => null,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}
