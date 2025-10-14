<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    /** @use HasFactory<\Database\Factories\RegionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
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

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    // Business Methods

    public function getStationCount(): int
    {
        return $this->stations()->count();
    }

    public function getActiveStations()
    {
        return $this->stations()->where('is_active', true)->get();
    }

    public function getTotalExpectedHeadcount(): int
    {
        return $this->stations()->sum('expected_headcount');
    }

    public function getTotalActualHeadcount(): int
    {
        return $this->stations()
            ->with('staff')
            ->get()
            ->sum(fn ($station) => $station->staff()->count());
    }
}
