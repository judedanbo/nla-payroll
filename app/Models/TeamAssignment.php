<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\TeamAssignmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'headcount_session_id',
        'user_id',
        'station_id',
        'assigned_by',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the headcount session this assignment belongs to.
     */
    public function headcountSession(): BelongsTo
    {
        return $this->belongsTo(HeadcountSession::class);
    }

    /**
     * Get the user (auditor) assigned.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the station assigned to.
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Get the user who made the assignment.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if the assignment is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the assignment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the assignment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Mark the assignment as completed.
     */
    public function markCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'end_date' => now(),
        ]);
    }

    /**
     * Cancel the assignment.
     */
    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'end_date' => now(),
        ]);
    }
}
