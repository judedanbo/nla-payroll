<?php

namespace Database\Factories;

use App\Models\MonthlyPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentElement>
 */
class PaymentElementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['basic_salary', 'allowance', 'deduction']);

        $elementNames = [
            'basic_salary' => ['Basic Salary'],
            'allowance' => [
                'Housing Allowance',
                'Transport Allowance',
                'Medical Allowance',
                'Utility Allowance',
                'Fuel Allowance',
            ],
            'deduction' => [
                'SSNIT (Employee)',
                'Income Tax (PAYE)',
                'Tier 3 Pension',
                'Loan Repayment',
            ],
        ];

        return [
            'monthly_payment_id' => MonthlyPayment::factory(),
            'element_type' => $type,
            'element_name' => fake()->randomElement($elementNames[$type]),
            'amount' => fake()->randomFloat(2, 100, 3000),
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that this is a basic salary element.
     */
    public function basicSalary(): static
    {
        return $this->state(fn (array $attributes) => [
            'element_type' => 'basic_salary',
            'element_name' => 'Basic Salary',
            'amount' => fake()->randomFloat(2, 2000, 8000),
        ]);
    }

    /**
     * Indicate that this is an allowance.
     */
    public function allowance(?string $name = null): static
    {
        $allowances = [
            'Housing Allowance',
            'Transport Allowance',
            'Medical Allowance',
            'Utility Allowance',
            'Fuel Allowance',
            'Responsibility Allowance',
        ];

        return $this->state(fn (array $attributes) => [
            'element_type' => 'allowance',
            'element_name' => $name ?? fake()->randomElement($allowances),
            'amount' => fake()->randomFloat(2, 200, 2000),
        ]);
    }

    /**
     * Indicate that this is a deduction.
     */
    public function deduction(?string $name = null): static
    {
        $deductions = [
            'SSNIT (Employee)',
            'Income Tax (PAYE)',
            'Tier 3 Pension',
            'Loan Repayment',
            'Salary Advance',
        ];

        return $this->state(fn (array $attributes) => [
            'element_type' => 'deduction',
            'element_name' => $name ?? fake()->randomElement($deductions),
            'amount' => fake()->randomFloat(2, 100, 1500),
        ]);
    }
}
