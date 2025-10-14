<?php

namespace App\Models;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\Severity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discrepancy extends Model
{
    /** @use HasFactory<\Database\Factories\DiscrepancyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'discrepancy_type',
        'severity',
        'description',
        'status',
        'detected_by',
        'detected_at',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'discrepancy_type' => DiscrepancyType::class,
            'severity' => Severity::class,
            'status' => DiscrepancyStatus::class,
        ];
    }

    // Relationships

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function detectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'detected_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DiscrepancyNote::class);
    }

    public function resolution(): HasOne
    {
        return $this->hasOne(DiscrepancyResolution::class);
    }

    // Business Methods

    /**
     * Check if discrepancy is open.
     */
    public function isOpen(): bool
    {
        return $this->status === DiscrepancyStatus::Open;
    }

    /**
     * Check if discrepancy is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === DiscrepancyStatus::Resolved;
    }

    /**
     * Check if discrepancy is critical severity.
     */
    public function isCritical(): bool
    {
        return $this->severity === Severity::Critical;
    }

    /**
     * Mark discrepancy as under review.
     */
    public function markUnderReview(): void
    {
        $this->update(['status' => DiscrepancyStatus::UnderReview]);
    }

    /**
     * Mark discrepancy as resolved.
     */
    public function markResolved(): void
    {
        $this->update(['status' => DiscrepancyStatus::Resolved]);
    }

    /**
     * Dismiss the discrepancy.
     */
    public function dismiss(?string $reason = null): void
    {
        $this->update(['status' => DiscrepancyStatus::Dismissed]);

        if ($reason) {
            $this->notes()->create([
                'created_by' => auth()->id(),
                'note_content' => 'Dismissed: '.$reason,
                'is_internal' => true,
            ]);
        }
    }

    /**
     * Add a note to this discrepancy.
     */
    public function addNote(int $createdBy, string $content, bool $isInternal = false): DiscrepancyNote
    {
        return $this->notes()->create([
            'created_by' => $createdBy,
            'note_content' => $content,
            'is_internal' => $isInternal,
        ]);
    }

    /**
     * Resolve the discrepancy.
     */
    public function resolve(int $resolvedBy, string $resolutionType, string $notes, string $outcome = 'resolved'): DiscrepancyResolution
    {
        $this->markResolved();

        return $this->resolution()->create([
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
            'resolution_type' => $resolutionType,
            'resolution_notes' => $notes,
            'outcome' => $outcome,
        ]);
    }

    /**
     * Get discrepancy type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->discrepancy_type) {
            'ghost_employee' => 'Ghost Employee',
            'duplicate_bank_account' => 'Duplicate Bank Account',
            'station_mismatch' => 'Station Mismatch',
            'salary_anomaly' => 'Salary Anomaly',
            'missing_data' => 'Missing Data',
            'unregistered_personnel' => 'Unregistered Personnel',
            'other' => 'Other',
            default => $this->discrepancy_type,
        };
    }

    /**
     * Get severity badge color.
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }
}
