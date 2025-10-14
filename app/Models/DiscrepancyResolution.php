<?php

namespace App\Models;

use App\Enums\ResolutionOutcome;
use App\Enums\ResolutionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscrepancyResolution extends Model
{
    /** @use HasFactory<\Database\Factories\DiscrepancyResolutionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'discrepancy_id',
        'resolved_by',
        'resolved_at',
        'resolution_type',
        'resolution_notes',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'resolution_type' => ResolutionType::class,
            'outcome' => ResolutionOutcome::class,
        ];
    }

    // Relationships

    public function discrepancy(): BelongsTo
    {
        return $this->belongsTo(Discrepancy::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Business Methods

    /**
     * Check if resolution was successful.
     */
    public function isResolved(): bool
    {
        return $this->outcome === ResolutionOutcome::Resolved;
    }

    /**
     * Check if resolution was escalated.
     */
    public function wasEscalated(): bool
    {
        return $this->resolution_type === ResolutionType::Escalated;
    }

    /**
     * Get resolution type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->resolution_type) {
            'corrected' => 'Data Corrected',
            'verified_valid' => 'Verified as Valid',
            'staff_removed' => 'Staff Removed from System',
            'data_updated' => 'Data Updated',
            'no_action_required' => 'No Action Required',
            'escalated' => 'Escalated to Management',
            default => $this->resolution_type,
        };
    }

    /**
     * Get time taken to resolve (in days).
     */
    public function getResolutionTime(): ?int
    {
        if (! $this->discrepancy) {
            return null;
        }

        return $this->discrepancy->detected_at->diffInDays($this->resolved_at);
    }
}
