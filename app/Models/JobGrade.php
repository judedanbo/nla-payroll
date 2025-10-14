<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobGrade extends Model
{
    /** @use HasFactory<\Database\Factories\JobGradeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'level',
        'min_salary',
        'max_salary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function jobTitles(): HasMany
    {
        return $this->hasMany(JobTitle::class);
    }

    // Business Methods

    public function getSalaryRange(): array
    {
        return [
            'min' => $this->min_salary,
            'max' => $this->max_salary,
        ];
    }

    public function validateSalary(float $amount): bool
    {
        return $amount >= $this->min_salary && $amount <= $this->max_salary;
    }

    public function getStaffWithGrade()
    {
        return Staff::whereHas('jobTitle.jobGrade', function ($query) {
            $query->where('id', $this->id);
        })->get();
    }
}
