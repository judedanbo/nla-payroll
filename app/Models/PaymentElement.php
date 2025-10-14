<?php

namespace App\Models;

use App\Enums\PaymentElementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentElement extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentElementFactory> */
    use HasFactory;

    protected $fillable = [
        'monthly_payment_id',
        'element_type',
        'element_name',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'element_type' => PaymentElementType::class,
        ];
    }

    // Relationships

    public function monthlyPayment(): BelongsTo
    {
        return $this->belongsTo(MonthlyPayment::class);
    }

    // Business Methods

    /**
     * Check if this is a deduction.
     */
    public function isDeduction(): bool
    {
        return $this->element_type === PaymentElementType::Deduction;
    }

    /**
     * Check if this is an allowance.
     */
    public function isAllowance(): bool
    {
        return $this->element_type === PaymentElementType::Allowance;
    }

    /**
     * Check if this is basic salary.
     */
    public function isBasicSalary(): bool
    {
        return $this->element_type === PaymentElementType::BasicSalary;
    }

    /**
     * Common Ghana payroll element names.
     */
    public static function getCommonElementNames(string $type): array
    {
        return match ($type) {
            'basic_salary' => ['Basic Salary'],
            'allowance' => [
                'Housing Allowance',
                'Transport Allowance',
                'Medical Allowance',
                'Utility Allowance',
                'Fuel Allowance',
                'Responsibility Allowance',
                'Entertainment Allowance',
            ],
            'deduction' => [
                'SSNIT (Employee)',
                'Income Tax (PAYE)',
                'Tier 3 Pension',
                'Loan Repayment',
                'Salary Advance',
                'Uniform Deduction',
                'Overpayment Recovery',
            ],
            default => [],
        };
    }
}
