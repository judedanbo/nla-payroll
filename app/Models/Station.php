<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    /** @use HasFactory<\Database\Factories\StationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'region_id',
        'name',
        'code',
        'address',
        'city',
        'latitude',
        'longitude',
        'gps_boundary',
        'expected_headcount',
        'contact_person',
        'contact_phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'gps_boundary' => 'array',
            'expected_headcount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function teamAssignments(): HasMany
    {
        return $this->hasMany(TeamAssignment::class);
    }

    public function headcountSessions(): HasMany
    {
        return $this->hasMany(HeadcountSession::class);
    }

    public function discrepancies(): HasMany
    {
        return $this->hasMany(Discrepancy::class);
    }

    // Business Methods

    public function getExpectedHeadcount(): int
    {
        return $this->expected_headcount;
    }

    public function getActualHeadcount(): int
    {
        return $this->staff()->count();
    }

    public function getVariance(): int
    {
        return $this->getActualHeadcount() - $this->getExpectedHeadcount();
    }

    public function isFullyCovered(): bool
    {
        // Check if all expected staff have been verified
        $verifiedCount = $this->staff()
            ->whereHas('headcountVerifications', function ($query) {
                $query->whereNotNull('verified_at')
                    ->where('station_id', $this->id);
            })
            ->count();

        return $verifiedCount >= $this->expected_headcount;
    }

    public function getAssignedTeams()
    {
        return $this->teamAssignments()
            ->with('user')
            ->where('is_active', true)
            ->get();
    }

    public function getGPSBoundary(): ?array
    {
        return $this->gps_boundary;
    }

    public function validateGPSLocation(float $lat, float $lng): bool
    {
        if (! $this->gps_boundary) {
            // If no boundary is defined, just check proximity to station coordinates
            if (! $this->latitude || ! $this->longitude) {
                return false;
            }

            // Calculate distance using Haversine formula (approximate)
            $earthRadius = 6371; // km
            $dLat = deg2rad($lat - $this->latitude);
            $dLng = deg2rad($lng - $this->longitude);

            $a = sin($dLat / 2) * sin($dLat / 2) +
                cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
                sin($dLng / 2) * sin($dLng / 2);

            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distance = $earthRadius * $c;

            // Within 5km of station
            return $distance <= 5;
        }

        // Check if point is within polygon boundary
        return $this->isPointInPolygon($lat, $lng, $this->gps_boundary);
    }

    protected function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        // Ray casting algorithm for point in polygon
        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            if ((($polygon[$i]['lat'] > $lat) != ($polygon[$j]['lat'] > $lat)) &&
                ($lng < ($polygon[$j]['lng'] - $polygon[$i]['lng']) * ($lat - $polygon[$i]['lat']) / ($polygon[$j]['lat'] - $polygon[$i]['lat']) + $polygon[$i]['lng'])) {
                $inside = ! $inside;
            }
            $j = $i;
        }

        return $inside;
    }
}
