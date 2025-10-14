<?php

namespace App\Services;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\Severity;
use App\Models\Discrepancy;
use App\Models\HeadcountVerification;
use App\Models\Staff;
use App\Models\Station;
use Illuminate\Support\Facades\Log;

class StationMismatchDetectionService
{
    /**
     * Maximum allowed distance from assigned station (in kilometers).
     */
    protected float $maxAllowedDistanceKm = 5.0;

    /**
     * Run station mismatch detection.
     *
     * Returns count of new discrepancies created.
     */
    public function detect(): int
    {
        $detectedCount = 0;

        Log::info('Starting station mismatch detection');

        // Get all verifications with GPS location data
        $verificationsWithGPS = HeadcountVerification::whereNotNull('location')
            ->with(['staff.station', 'headcountSession'])
            ->get();

        foreach ($verificationsWithGPS as $verification) {
            $staff = $verification->staff;

            if (! $staff || ! $staff->station) {
                continue;
            }

            // Parse verification location
            $verificationLocation = json_decode($verification->location, true);

            if (! isset($verificationLocation['latitude']) || ! isset($verificationLocation['longitude'])) {
                continue;
            }

            $verifiedLat = $verificationLocation['latitude'];
            $verifiedLng = $verificationLocation['longitude'];

            // Get assigned station coordinates
            $assignedStation = $staff->station;

            if (! $assignedStation->latitude || ! $assignedStation->longitude) {
                // Station doesn't have GPS coordinates, skip
                continue;
            }

            // Calculate distance between verification location and assigned station
            $distance = $this->calculateDistance(
                $verifiedLat,
                $verifiedLng,
                $assignedStation->latitude,
                $assignedStation->longitude
            );

            // Check if distance exceeds threshold
            if ($distance > $this->maxAllowedDistanceKm) {
                // Check if discrepancy already exists for this verification
                if ($this->discrepancyExistsForVerification($verification->id)) {
                    continue;
                }

                $description = sprintf(
                    'Staff member %s was verified at a location %.2f km away from their assigned station (%s). They were verified at GPS coordinates (%.6f, %.6f) during %s session, but their assigned station (%s) is located at (%.6f, %.6f). Maximum allowed distance is %.1f km.',
                    $staff->full_name,
                    $distance,
                    $assignedStation->name,
                    $verifiedLat,
                    $verifiedLng,
                    $verification->headcountSession->session_name,
                    $assignedStation->name,
                    $assignedStation->latitude,
                    $assignedStation->longitude,
                    $this->maxAllowedDistanceKm
                );

                // Determine severity based on distance
                $severity = $this->determineSeverity($distance);

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staff->id,
                    'discrepancy_type' => DiscrepancyType::StationMismatch,
                    'severity' => $severity,
                    'status' => DiscrepancyStatus::Open,
                    'description' => $description,
                    'detected_by' => 1,
                    'detected_at' => now(),
                ]);

                $detectedCount++;
            }
        }

        Log::info("Station mismatch detection completed. Found {$detectedCount} new discrepancies");

        return $detectedCount;
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula.
     *
     * @return float Distance in kilometers
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Determine severity based on distance from assigned station.
     */
    protected function determineSeverity(float $distanceKm): Severity
    {
        if ($distanceKm > 50) {
            return Severity::Critical; // >50km is extremely suspicious
        } elseif ($distanceKm > 20) {
            return Severity::High; // >20km is very suspicious
        } elseif ($distanceKm > 10) {
            return Severity::Medium; // >10km is suspicious
        } else {
            return Severity::Low; // 5-10km might be legitimate (nearby location)
        }
    }

    /**
     * Check if discrepancy already exists for this verification.
     */
    protected function discrepancyExistsForVerification(int $verificationId): bool
    {
        // Get verification to find staff_id
        $verification = HeadcountVerification::find($verificationId);

        if (! $verification) {
            return false;
        }

        // Check if discrepancy exists for this staff with station mismatch type
        // and was created recently (within 24 hours of verification)
        return Discrepancy::where('staff_id', $verification->staff_id)
            ->where('discrepancy_type', DiscrepancyType::StationMismatch)
            ->where('detected_at', '>=', $verification->verified_at->subHours(24))
            ->where('detected_at', '<=', $verification->verified_at->addHours(24))
            ->exists();
    }

    /**
     * Get station mismatch statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_station_mismatch_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::StationMismatch)->count(),
            'open_station_mismatch_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::StationMismatch)
                ->where('status', DiscrepancyStatus::Open)
                ->count(),
            'critical_station_mismatches' => Discrepancy::where('discrepancy_type', DiscrepancyType::StationMismatch)
                ->where('severity', Severity::Critical)
                ->where('status', '!=', DiscrepancyStatus::Dismissed)
                ->count(),
            'staff_with_station_mismatches' => Discrepancy::where('discrepancy_type', DiscrepancyType::StationMismatch)
                ->where('status', '!=', DiscrepancyStatus::Dismissed)
                ->distinct('staff_id')
                ->count('staff_id'),
        ];
    }
}
