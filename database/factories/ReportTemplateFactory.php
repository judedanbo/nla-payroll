<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $templates = [
            'ghost_employees' => [
                'name' => 'Ghost Employees Detection',
                'description' => 'Identifies potential ghost employees through attendance and verification patterns',
                'query_template' => 'SELECT * FROM staff WHERE id NOT IN (SELECT staff_id FROM headcount_verifications WHERE verified_at IS NOT NULL)',
            ],
            'salary_anomalies' => [
                'name' => 'Salary Anomaly Detection',
                'description' => 'Detects unusual salary patterns and discrepancies',
                'query_template' => 'SELECT * FROM staff WHERE monthly_salary > (SELECT AVG(monthly_salary) * 2 FROM staff)',
            ],
            'headcount_summary' => [
                'name' => 'Headcount Session Summary',
                'description' => 'Comprehensive summary of headcount verification sessions',
                'query_template' => 'SELECT * FROM headcount_sessions WHERE session_date BETWEEN :start_date AND :end_date',
            ],
            'discrepancy_analysis' => [
                'name' => 'Discrepancy Analysis',
                'description' => 'Analyzes all discrepancies by type, severity, and resolution status',
                'query_template' => 'SELECT * FROM discrepancies WHERE status = :status AND severity = :severity',
            ],
        ];

        $type = fake()->randomElement(array_keys($templates));
        $template = $templates[$type];

        return [
            'name' => $template['name'],
            'description' => $template['description'],
            'type' => $type,
            'query_template' => $template['query_template'],
            'parameters_schema' => [
                'start_date' => [
                    'type' => 'date',
                    'required' => true,
                    'label' => 'Start Date',
                ],
                'end_date' => [
                    'type' => 'date',
                    'required' => true,
                    'label' => 'End Date',
                ],
                'region_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'label' => 'Region',
                ],
            ],
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Indicate the template is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate the template is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
