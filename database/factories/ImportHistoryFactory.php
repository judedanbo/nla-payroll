<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportHistory>
 */
class ImportHistoryFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(50, 500);
        $successful = fake()->numberBetween(40, $total);

        return [
            'uploaded_by' => User::factory(),
            'file_name' => fake()->word().'-'.fake()->date().'.csv',
            'file_path' => 'imports/'.fake()->uuid().'.csv',
            'import_type' => fake()->randomElement(['staff', 'bank_details', 'payroll']),
            'total_records' => $total,
            'successful_records' => $successful,
            'failed_records' => $total - $successful,
            'status' => fake()->randomElement(['completed', 'processing', 'failed']),
            'started_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'completed_at' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
