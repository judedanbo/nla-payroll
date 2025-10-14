<?php

namespace Database\Factories;

use App\Models\ImportHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportError>
 */
class ImportErrorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'import_history_id' => ImportHistory::factory(),
            'row_number' => fake()->numberBetween(1, 1000),
            'field_name' => fake()->randomElement(['staff_number', 'bank_account', 'email', 'phone', 'department']),
            'error_message' => fake()->randomElement([
                'Invalid email format',
                'Duplicate staff number',
                'Bank account number must be 10 digits',
                'Required field is missing',
            ]),
            'row_data' => [
                'staff_number' => 'STF-'.fake()->numerify('#####'),
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
            ],
        ];
    }
}
