<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitle extends Model
{
    /** @use HasFactory<\Database\Factories\JobTitleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_grade_id',
        'name',
        'code',
        'description',
        'responsibilities',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function jobGrade(): BelongsTo
    {
        return $this->belongsTo(JobGrade::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    // Business Methods

    public function getSalaryRange(): array
    {
        return $this->jobGrade->getSalaryRange();
    }

    public function validateSalary(float $amount): bool
    {
        return $this->jobGrade->validateSalary($amount);
    }

    public function getStaffWithTitle()
    {
        return $this->staff()->where('is_active', true)->get();
    }

    public function getHistoricalChanges()
    {
        // This would track changes to the job title over time
        // Could be implemented with audit logs or a dedicated history table
        return collect();
    }
}
