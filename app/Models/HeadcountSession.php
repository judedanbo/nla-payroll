<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeadcountSession extends Model
{
    /** @use HasFactory<\Database\Factories\HeadcountSessionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'session_name',
        'description',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // Relationships

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(HeadcountVerification::class);
    }

    // Business Methods

    /**
     * Check if session is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Start the headcount session.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'start_date' => $this->start_date ?? now(),
        ]);
    }

    /**
     * Complete the headcount session.
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'end_date' => now(),
        ]);
    }

    /**
     * Cancel the headcount session.
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'notes' => $reason ?? $this->notes,
        ]);
    }

    /**
     * Get verification statistics for this session.
     */
    public function getVerificationStats(): array
    {
        return [
            'total' => $this->verifications()->count(),
            'present' => $this->verifications()->where('verification_status', 'present')->count(),
            'absent' => $this->verifications()->where('verification_status', 'absent')->count(),
            'on_leave' => $this->verifications()->where('verification_status', 'on_leave')->count(),
            'ghost' => $this->verifications()->where('verification_status', 'ghost')->count(),
        ];
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        $totalStaff = Staff::where('is_active', true)->count();

        if ($totalStaff === 0) {
            return 0;
        }

        $verified = $this->verifications()->count();

        return round(($verified / $totalStaff) * 100, 2);
    }
}
