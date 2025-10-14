<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    // Business Methods

    public function getHeadcount(): int
    {
        return $this->staff()->count();
    }

    public function getVerifiedCount(): int
    {
        return $this->staff()
            ->whereHas('headcountVerifications', function ($query) {
                $query->whereNotNull('verified_at');
            })
            ->count();
    }

    public function getUnverifiedStaff()
    {
        return $this->staff()
            ->whereDoesntHave('headcountVerifications', function ($query) {
                $query->whereNotNull('verified_at');
            })
            ->get();
    }

    public function getPayrollTotal(): float
    {
        return $this->staff()
            ->whereHas('monthlyPayments', function ($query) {
                $query->whereYear('payment_month', now()->year)
                    ->whereMonth('payment_month', now()->month);
            })
            ->with('monthlyPayments')
            ->get()
            ->sum(fn ($staff) => $staff->monthlyPayments->sum('net_pay'));
    }

    public function getSubDepartments()
    {
        return $this->children;
    }

    public function getHierarchyTree(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'children' => $this->children->map(fn ($child) => $child->getHierarchyTree())->toArray(),
        ];
    }
}
