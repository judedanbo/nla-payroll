<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MonthlyPayment>
 */
class MonthlyPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grossAmount = fake()->randomFloat(2, 2000, 15000);
        $deductionsTotal = $grossAmount * fake()->randomFloat(2, 0.15, 0.25); // 15-25% deductions
        $netAmount = $grossAmount - $deductionsTotal;

        return [
            'staff_id' => Staff::factory(),
            'payment_month' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-01'), // First day of month
            'gross_amount' => $grossAmount,
            'deductions_total' => $deductionsTotal,
            'net_amount' => $netAmount,
            'payment_status' => fake()->randomElement(['pending', 'approved', 'processing', 'paid']),
            'payment_date' => null,
            'payment_reference' => null,
            'approved_by' => null,
            'approved_at' => null,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'pending',
            'payment_date' => null,
            'payment_reference' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the payment has been approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the payment has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'payment_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'payment_reference' => 'PAY-'.fake()->unique()->numerify('######'),
            'approved_by' => User::factory(),
            'approved_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the payment has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'failed',
            'notes' => fake()->randomElement([
                'Insufficient funds in payroll account',
                'Invalid bank account details',
                'Bank system error',
                'Payment rejected by bank',
            ]),
        ]);
    }
}
