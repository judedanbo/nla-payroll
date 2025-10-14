<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bank extends Model
{
    /** @use HasFactory<\Database\Factories\BankFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'swift_code',
        'branch_name',
        'branch_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function bankDetails(): HasMany
    {
        return $this->hasMany(BankDetail::class);
    }

    // Business Methods

    public function getActiveAccountCount(): int
    {
        return $this->bankDetails()->where('is_active', true)->count();
    }

    public function getTotalStaffCount(): int
    {
        return $this->bankDetails()
            ->distinct('staff_id')
            ->count('staff_id');
    }
}
