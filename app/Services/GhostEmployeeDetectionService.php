<?php

namespace App\Services;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\Severity;
use App\Enums\VerificationStatus;
use App\Models\Discrepancy;
use App\Models\HeadcountVerification;
use App\Models\MonthlyPayment;
use App\Models\Staff;
use Illuminate\Support\Facades\Log;

class GhostEmployeeDetectionService
{
    /**
     * Minimum number of consecutive absences to flag as ghost employee.
     */
    protected int $consecutiveAbsenceThreshold = 3;

    /**
     * Minimum number of months receiving payments without verification.
     */
    protected int $paymentWithoutVerificationMonths = 3;

    /**
     * Run full ghost employee detection.
     *
     * Returns count of new discrepancies created.
     */
    public function detect(): int
    {
        $detectedCount = 0;

        Log::info('Starting ghost employee detection');

        // Detection Method 1: Staff receiving payments but never verified
        $detectedCount += $this->detectPaymentWithoutVerification();

        // Detection Method 2: Staff with consecutive absences/ghost flags
        $detectedCount += $this->detectConsecutiveAbsences();

        // Detection Method 3: Staff marked as ghost but still receiving payments
        $detectedCount += $this->detectGhostStillPaid();

        Log::info("Ghost employee detection completed. Found {$detectedCount} new discrepancies");

        return $detectedCount;
    }

    /**
     * Detect staff receiving payments but never verified in headcount.
     */
    protected function detectPaymentWithoutVerification(): int
    {
        // Get staff who received payments in last N months
        $staffWithRecentPayments = MonthlyPayment::where('payment_date', '>=', now()->subMonths($this->paymentWithoutVerificationMonths))
            ->select('staff_id')
            ->distinct()
            ->pluck('staff_id');

        // Get staff who have been verified
        $staffWithVerifications = HeadcountVerification::select('staff_id')
            ->distinct()
            ->pluck('staff_id');

        // Find staff with payments but no verifications
        $suspectStaffIds = $staffWithRecentPayments->diff($staffWithVerifications);

        if ($suspectStaffIds->isEmpty()) {
            return 0;
        }

        $count = 0;

        foreach ($suspectStaffIds as $staffId) {
            // Check if discrepancy already exists
            if ($this->discrepancyExists($staffId, DiscrepancyType::GhostEmployee)) {
                continue;
            }

            $staff = Staff::find($staffId);

            if (! $staff || ! $staff->is_active) {
                continue;
            }

            // Count payments
            $paymentCount = MonthlyPayment::where('staff_id', $staffId)
                ->where('payment_date', '>=', now()->subMonths($this->paymentWithoutVerificationMonths))
                ->count();

            // Calculate total amount paid
            $totalPaid = MonthlyPayment::where('staff_id', $staffId)
                ->where('payment_date', '>=', now()->subMonths($this->paymentWithoutVerificationMonths))
                ->sum('net_salary');

            $description = sprintf(
                'Staff member has received %d monthly payments totaling GHS %s over the past %d months but has NEVER been verified in any headcount session. This is a strong indicator of a ghost employee.',
                $paymentCount,
                number_format($totalPaid, 2),
                $this->paymentWithoutVerificationMonths
            );

            // Create discrepancy
            Discrepancy::create([
                'staff_id' => $staffId,
                'discrepancy_type' => DiscrepancyType::GhostEmployee,
                'severity' => Severity::Critical,
                'status' => DiscrepancyStatus::Open,
                'description' => $description,
                'detected_by' => 1, // System user ID (you may want to create a system user)
                'detected_at' => now(),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Detect staff with consecutive absences or ghost flags in verifications.
     */
    protected function detectConsecutiveAbsences(): int
    {
        // Get all staff who have been verified
        $verifiedStaffIds = HeadcountVerification::select('staff_id')
            ->distinct()
            ->pluck('staff_id');

        $count = 0;

        foreach ($verifiedStaffIds as $staffId) {
            // Check if discrepancy already exists
            if ($this->discrepancyExists($staffId, DiscrepancyType::GhostEmployee)) {
                continue;
            }

            // Get verification history ordered by date
            $verifications = HeadcountVerification::where('staff_id', $staffId)
                ->with('headcountSession')
                ->orderBy('verified_at')
                ->get();

            // Count consecutive absences/ghost flags
            $consecutiveAbsences = 0;
            $ghostFlagCount = 0;

            foreach ($verifications as $verification) {
                if ($verification->verification_status === VerificationStatus::Absent ||
                    $verification->verification_status === VerificationStatus::Ghost) {
                    $consecutiveAbsences++;

                    if ($verification->verification_status === VerificationStatus::Ghost) {
                        $ghostFlagCount++;
                    }
                } else {
                    // Reset counter if present
                    $consecutiveAbsences = 0;
                }
            }

            // Flag if consecutive absences meet threshold OR if flagged as ghost
            if ($consecutiveAbsences >= $this->consecutiveAbsenceThreshold || $ghostFlagCount > 0) {
                $staff = Staff::find($staffId);

                if (! $staff || ! $staff->is_active) {
                    continue;
                }

                $description = $ghostFlagCount > 0
                    ? sprintf(
                        'Staff member was explicitly flagged as GHOST %d time(s) during headcount verifications. Additionally, they have been marked absent in %d consecutive verification sessions.',
                        $ghostFlagCount,
                        $consecutiveAbsences
                    )
                    : sprintf(
                        'Staff member has been marked absent in %d consecutive headcount verification sessions, indicating possible ghost employee.',
                        $consecutiveAbsences
                    );

                $severity = $ghostFlagCount > 0 ? Severity::Critical : Severity::High;

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staffId,
                    'discrepancy_type' => DiscrepancyType::GhostEmployee,
                    'severity' => $severity,
                    'status' => DiscrepancyStatus::Open,
                    'description' => $description,
                    'detected_by' => 1,
                    'detected_at' => now(),
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Detect staff marked as ghost in records but still receiving payments.
     */
    protected function detectGhostStillPaid(): int
    {
        $count = 0;

        // Find staff marked as ghost (is_ghost flag)
        $ghostStaff = Staff::where('is_ghost', true)->get();

        foreach ($ghostStaff as $staff) {
            // Check if already has this discrepancy
            if ($this->discrepancyExists($staff->id, DiscrepancyType::GhostEmployee)) {
                continue;
            }

            // Check if still receiving payments
            $recentPaymentCount = MonthlyPayment::where('staff_id', $staff->id)
                ->where('payment_date', '>=', now()->subMonths(3))
                ->count();

            if ($recentPaymentCount > 0) {
                $totalPaid = MonthlyPayment::where('staff_id', $staff->id)
                    ->where('payment_date', '>=', now()->subMonths(3))
                    ->sum('net_salary');

                $description = sprintf(
                    'Staff member is flagged as GHOST in the system (is_ghost=true) but has received %d payments totaling GHS %s in the past 3 months. Payments should be stopped immediately.',
                    $recentPaymentCount,
                    number_format($totalPaid, 2)
                );

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staff->id,
                    'discrepancy_type' => DiscrepancyType::GhostEmployee,
                    'severity' => Severity::Critical,
                    'status' => DiscrepancyStatus::Open,
                    'description' => $description,
                    'detected_by' => 1,
                    'detected_at' => now(),
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if discrepancy already exists for staff and type.
     */
    protected function discrepancyExists(int $staffId, DiscrepancyType $type): bool
    {
        return Discrepancy::where('staff_id', $staffId)
            ->where('discrepancy_type', $type)
            ->where('status', '!=', DiscrepancyStatus::Dismissed)
            ->exists();
    }

    /**
     * Get ghost employee statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_ghost_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::GhostEmployee)->count(),
            'open_ghost_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::GhostEmployee)
                ->where('status', DiscrepancyStatus::Open)
                ->count(),
            'resolved_ghost_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::GhostEmployee)
                ->where('status', DiscrepancyStatus::Resolved)
                ->count(),
            'staff_flagged_as_ghost' => Staff::where('is_ghost', true)->count(),
            'ghost_employees_still_paid' => $this->countGhostStillPaid(),
        ];
    }

    /**
     * Count ghost employees still receiving payments.
     */
    protected function countGhostStillPaid(): int
    {
        return Staff::where('is_ghost', true)
            ->whereHas('monthlyPayments', function ($query) {
                $query->where('payment_date', '>=', now()->subMonths(3));
            })
            ->count();
    }
}
