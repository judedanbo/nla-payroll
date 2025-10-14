<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\StaffAssignmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'department_id',
        'unit_id',
        'station_id',
        'assignment_date',
        'end_date',
        'is_current',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assignment_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    // Relationships

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    // Business Methods

    /**
     * Check if this is the current assignment.
     */
    public function isCurrent(): bool
    {
        return $this->is_current && $this->end_date === null;
    }

    /**
     * End this assignment.
     */
    public function end(?string $notes = null): void
    {
        $this->update([
            'end_date' => now(),
            'is_current' => false,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Create history record when assignment changes.
     */
    public function makeHistory(int $newDepartmentId, int $newUnitId, int $newStationId, ?int $changedBy = null, ?string $reason = null): StaffAssignmentHistory
    {
        return StaffAssignmentHistory::create([
            'staff_id' => $this->staff_id,
            'changed_by' => $changedBy,
            'old_department_id' => $this->department_id,
            'old_unit_id' => $this->unit_id,
            'old_station_id' => $this->station_id,
            'new_department_id' => $newDepartmentId,
            'new_unit_id' => $newUnitId,
            'new_station_id' => $newStationId,
            'changed_at' => now(),
            'reason' => $reason,
        ]);
    }
}
