<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $generatedAt = fake()->dateTimeBetween('-6 months', 'now');

        $reportTypes = [
            'ghost_employees' => 'Ghost Employees Report',
            'salary_anomalies' => 'Salary Anomalies Report',
            'headcount_summary' => 'Headcount Summary Report',
            'discrepancy_analysis' => 'Discrepancy Analysis Report',
        ];

        $type = fake()->randomElement(array_keys($reportTypes));

        return [
            'report_template_id' => \App\Models\ReportTemplate::factory(),
            'generated_by' => \App\Models\User::factory(),
            'title' => $reportTypes[$type].' - '.fake()->date('F Y', $generatedAt),
            'parameters' => [
                'start_date' => fake()->date('Y-m-d', '-1 month'),
                'end_date' => fake()->date('Y-m-d'),
                'region_id' => fake()->numberBetween(1, 10),
                'include_details' => fake()->boolean(),
            ],
            'file_path' => fake()->boolean(80) ? 'reports/'.fake()->uuid().'.pdf' : null,
            'generated_at' => $generatedAt,
        ];
    }

    /**
     * Indicate the report has a file.
     */
    public function withFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'reports/'.fake()->uuid().'.pdf',
        ]);
    }

    /**
     * Indicate the report has no file.
     */
    public function withoutFile(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => null,
        ]);
    }
}
