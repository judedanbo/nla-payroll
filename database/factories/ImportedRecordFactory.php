<?php

namespace Database\Factories;

use App\Models\ImportHistory;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportedRecord>
 */
class ImportedRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'import_history_id' => ImportHistory::factory(),
            'recordable_type' => Staff::class,
            'recordable_id' => Staff::factory(),
            'status' => fake()->randomElement(['processed', 'pending', 'failed']),
            'original_data' => [
                'staff_number' => 'STF-'.fake()->numerify('#####'),
                'name' => fake()->name(),
                'department' => fake()->word(),
            ],
        ];
    }
}
