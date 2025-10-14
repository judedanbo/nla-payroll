<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAssignmentHistory extends Model
{
    /** @use HasFactory<\Database\Factories\StaffAssignmentHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'changed_by',
        'old_department_id',
        'old_unit_id',
        'old_station_id',
        'new_department_id',
        'new_unit_id',
        'new_station_id',
        'changed_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    // Relationships

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function oldDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'old_department_id');
    }

    public function oldUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'old_unit_id');
    }

    public function oldStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'old_station_id');
    }

    public function newDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'new_department_id');
    }

    public function newUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'new_unit_id');
    }

    public function newStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'new_station_id');
    }

    // Business Methods

    /**
     * Get a human-readable summary of the assignment change.
     */
    public function getChangeSummary(): string
    {
        $changes = [];

        if ($this->old_department_id !== $this->new_department_id) {
            $changes[] = "Department: {$this->oldDepartment->name} → {$this->newDepartment->name}";
        }

        if ($this->old_unit_id !== $this->new_unit_id) {
            $changes[] = "Unit: {$this->oldUnit->name} → {$this->newUnit->name}";
        }

        if ($this->old_station_id !== $this->new_station_id) {
            $changes[] = "Station: {$this->oldStation->name} → {$this->newStation->name}";
        }

        return implode(' | ', $changes);
    }
}
