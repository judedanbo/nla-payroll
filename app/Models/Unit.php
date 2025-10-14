<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    // Business Methods

    public function getStaffCount(): int
    {
        return $this->staff()->count();
    }

    public function getActiveStaff()
    {
        return $this->staff()->where('is_active', true)->get();
    }

    public function getHistoricalStaff($date)
    {
        return $this->staffAssignments()
            ->whereDate('assigned_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('unassigned_at')
                    ->orWhereDate('unassigned_at', '>=', $date);
            })
            ->with('staff')
            ->get()
            ->pluck('staff');
    }

    public function validateStaffing(): bool
    {
        // Implement staffing validation logic
        // This could check minimum/maximum staff requirements
        return true;
    }
}
