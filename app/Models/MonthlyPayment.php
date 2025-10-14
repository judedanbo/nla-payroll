<?php

namespace App\Models;

use App\Enums\PaymentElementType;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyPayment extends Model
{
    /** @use HasFactory<\Database\Factories\MonthlyPaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'payment_month',
        'gross_amount',
        'deductions_total',
        'net_amount',
        'payment_status',
        'payment_date',
        'payment_reference',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_month' => 'date',
            'payment_date' => 'date',
            'approved_at' => 'datetime',
            'gross_amount' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
        ];
    }

    // Relationships

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function paymentElements(): HasMany
    {
        return $this->hasMany(PaymentElement::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Business Methods

    /**
     * Check if payment has been paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * Check if payment has been approved.
     */
    public function isApproved(): bool
    {
        return in_array($this->payment_status, [PaymentStatus::Approved, PaymentStatus::Processing, PaymentStatus::Paid]);
    }

    /**
     * Approve the payment.
     */
    public function approve(int $userId): void
    {
        $this->update([
            'payment_status' => PaymentStatus::Approved,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark payment as paid.
     */
    public function markAsPaid(string $reference, ?string $notes = null): void
    {
        $this->update([
            'payment_status' => PaymentStatus::Paid,
            'payment_date' => now(),
            'payment_reference' => $reference,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'payment_status' => PaymentStatus::Failed,
            'notes' => $reason ?? $this->notes,
        ]);
    }

    /**
     * Recalculate totals from payment elements.
     */
    public function calculateTotals(): void
    {
        $basicAndAllowances = $this->paymentElements()
            ->whereIn('element_type', [PaymentElementType::BasicSalary, PaymentElementType::Allowance])
            ->sum('amount');

        $deductions = $this->paymentElements()
            ->where('element_type', PaymentElementType::Deduction)
            ->sum('amount');

        $this->update([
            'gross_amount' => $basicAndAllowances,
            'deductions_total' => abs($deductions),
            'net_amount' => $basicAndAllowances - abs($deductions),
        ]);
    }

    /**
     * Get breakdown by element type.
     */
    public function getBreakdown(): array
    {
        return [
            'basic_salary' => $this->paymentElements()
                ->where('element_type', PaymentElementType::BasicSalary)
                ->sum('amount'),
            'allowances' => $this->paymentElements()
                ->where('element_type', PaymentElementType::Allowance)
                ->sum('amount'),
            'deductions' => $this->paymentElements()
                ->where('element_type', PaymentElementType::Deduction)
                ->sum('amount'),
        ];
    }
}
