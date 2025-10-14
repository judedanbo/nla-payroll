<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    /** @use HasFactory<\Database\Factories\StaffFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'department_id',
        'unit_id',
        'job_title_id',
        'station_id',
        'staff_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'national_id',
        'gender',
        'marital_status',
        'email',
        'phone_primary',
        'phone_secondary',
        'address',
        'city',
        'region',
        'emergency_contact_name',
        'emergency_contact_phone',
        'date_of_hire',
        'date_of_termination',
        'employment_status',
        'employment_type',
        'current_salary',
        'is_verified',
        'last_verified_at',
        'is_ghost',
        'ghost_reason',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_of_hire' => 'date',
            'date_of_termination' => 'date',
            'current_salary' => 'decimal:2',
            'is_verified' => 'boolean',
            'last_verified_at' => 'datetime',
            'is_ghost' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function bankDetails(): HasMany
    {
        return $this->hasMany(BankDetail::class);
    }

    public function monthlyPayments(): HasMany
    {
        return $this->hasMany(MonthlyPayment::class);
    }

    public function staffAssignmentHistories(): HasMany
    {
        return $this->hasMany(StaffAssignmentHistory::class);
    }

    public function headcountVerifications(): HasMany
    {
        return $this->hasMany(HeadcountVerification::class);
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(Discrepancy::class);
    }

    // Data Validation Methods

    public function validateBioData(): bool
    {
        // Check if all required bio data fields are present
        return $this->staff_number &&
               $this->first_name &&
               $this->last_name &&
               $this->date_of_birth &&
               $this->gender &&
               $this->phone_primary &&
               $this->address;
    }

    public function hasCompleteBankDetails(): bool
    {
        return $this->bankDetails()->where('is_active', true)->exists();
    }

    public function getPaymentHistory($startDate = null, $endDate = null)
    {
        $query = $this->monthlyPayments();

        if ($startDate) {
            $query->whereDate('payment_month', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('payment_month', '<=', $endDate);
        }

        return $query->orderBy('payment_month', 'desc')->get();
    }

    public function calculateTotalPayments(?int $year = null): float
    {
        $query = $this->monthlyPayments();

        if ($year) {
            $query->whereYear('payment_month', $year);
        }

        return $query->sum('net_pay');
    }

    // Verification Status Methods

    public function isVerified(): bool
    {
        return $this->is_verified && $this->last_verified_at !== null;
    }

    public function getLastVerificationDate(): ?string
    {
        return $this->last_verified_at?->format('Y-m-d');
    }

    public function markAsVerified($auditorId, $location): void
    {
        $this->update([
            'is_verified' => true,
            'last_verified_at' => now(),
            'is_ghost' => false,
            'ghost_reason' => null,
        ]);

        // Create verification record
        $this->headcountVerifications()->create([
            'user_id' => $auditorId,
            'station_id' => $this->station_id,
            'verified_at' => now(),
            'verification_location' => $location,
            'verification_status' => 'verified',
        ]);
    }

    public function flagAsGhost(?string $reason = null): void
    {
        $this->update([
            'is_ghost' => true,
            'ghost_reason' => $reason,
            'is_verified' => false,
        ]);

        // Create discrepancy record
        $this->discrepancies()->create([
            'station_id' => $this->station_id,
            'user_id' => auth()->id(),
            'discrepancy_type' => 'ghost_employee',
            'description' => $reason ?? 'Staff flagged as potential ghost employee',
            'severity' => 'high',
            'status' => 'open',
        ]);
    }

    public function getDiscrepancies()
    {
        return $this->discrepancies()->where('status', '!=', 'resolved')->get();
    }

    // Assignment Methods

    public function getCurrentAssignment()
    {
        return [
            'department' => $this->department,
            'unit' => $this->unit,
            'job_title' => $this->jobTitle,
            'station' => $this->station,
        ];
    }

    public function getAssignmentHistory()
    {
        return $this->staffAssignmentHistories()
            ->with(['department', 'unit', 'jobTitle', 'station'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    public function hasStationMismatch(): bool
    {
        // Check if staff's current station matches their verification station
        $latestVerification = $this->headcountVerifications()->latest()->first();

        if (! $latestVerification) {
            return false;
        }

        return $latestVerification->station_id !== $this->station_id;
    }

    public function getDuplicateBankAccounts()
    {
        $accountNumbers = $this->bankDetails()->pluck('account_number')->toArray();

        if (empty($accountNumbers)) {
            return collect();
        }

        // Find other staff with the same account numbers
        return BankDetail::whereIn('account_number', $accountNumbers)
            ->where('staff_id', '!=', $this->id)
            ->with('staff')
            ->get()
            ->pluck('staff')
            ->unique('id');
    }

    // Helper Methods

    public function getFullName(): string
    {
        return trim($this->first_name.' '.$this->middle_name.' '.$this->last_name);
    }

    public function getAge(): int
    {
        return $this->date_of_birth->age;
    }

    public function getYearsOfService(): int
    {
        return $this->date_of_hire->diffInYears(now());
    }

    public function isActive(): bool
    {
        return $this->is_active && $this->employment_status === 'active';
    }

    public function getPrimaryBankAccount(): ?BankDetail
    {
        return $this->bankDetails()->where('is_primary', true)->where('is_active', true)->first();
    }
}
