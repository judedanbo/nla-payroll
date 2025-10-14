<?php

namespace App\Services;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\Severity;
use App\Models\Discrepancy;
use App\Models\MonthlyPayment;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryAnomalyDetectionService
{
    /**
     * Percentage threshold for salary range violations.
     */
    protected float $salaryRangeThresholdPercent = 20.0;

    /**
     * Percentage threshold for payment spikes.
     */
    protected float $paymentSpikeThresholdPercent = 50.0;

    /**
     * Minimum number of months of payment history required for spike detection.
     */
    protected int $minPaymentHistoryMonths = 3;

    /**
     * Run salary anomaly detection.
     *
     * Returns count of new discrepancies created.
     */
    public function detect(): int
    {
        $detectedCount = 0;

        Log::info('Starting salary anomaly detection');

        // Detection Method 1: Salaries outside job grade range
        $detectedCount += $this->detectSalaryRangeViolations();

        // Detection Method 2: Payment spikes and sudden changes
        $detectedCount += $this->detectPaymentSpikes();

        // Detection Method 3: Suspicious duplicate payment amounts
        $detectedCount += $this->detectSuspiciousDuplicatePayments();

        // Detection Method 4: Payment vs expected salary mismatch
        $detectedCount += $this->detectPaymentSalaryMismatch();

        Log::info("Salary anomaly detection completed. Found {$detectedCount} new discrepancies");

        return $detectedCount;
    }

    /**
     * Detect staff with salaries outside their job grade range.
     */
    protected function detectSalaryRangeViolations(): int
    {
        $count = 0;

        // Get active staff with job titles and grades
        $staff = Staff::where('is_active', true)
            ->whereNotNull('current_salary')
            ->with('jobTitle.jobGrade')
            ->get();

        foreach ($staff as $staffMember) {
            if (! $staffMember->jobTitle || ! $staffMember->jobTitle->jobGrade) {
                continue;
            }

            $jobGrade = $staffMember->jobTitle->jobGrade;
            $currentSalary = (float) $staffMember->current_salary;

            // Calculate acceptable range with threshold
            $minAcceptable = $jobGrade->min_salary * (1 - $this->salaryRangeThresholdPercent / 100);
            $maxAcceptable = $jobGrade->max_salary * (1 + $this->salaryRangeThresholdPercent / 100);

            // Check if salary is outside acceptable range
            if ($currentSalary < $minAcceptable || $currentSalary > $maxAcceptable) {
                // Check if discrepancy already exists
                if ($this->discrepancyExists($staffMember->id, DiscrepancyType::SalaryAnomaly)) {
                    continue;
                }

                $percentageDeviation = 0;
                $direction = '';

                if ($currentSalary < $minAcceptable) {
                    $percentageDeviation = (($jobGrade->min_salary - $currentSalary) / $jobGrade->min_salary) * 100;
                    $direction = 'below';
                } else {
                    $percentageDeviation = (($currentSalary - $jobGrade->max_salary) / $jobGrade->max_salary) * 100;
                    $direction = 'above';
                }

                $description = sprintf(
                    'Staff member\'s current salary (GHS %s) is %.1f%% %s the expected range for their job grade "%s" (GHS %s - GHS %s). This may indicate a data entry error or unauthorized salary adjustment.',
                    number_format($currentSalary, 2),
                    $percentageDeviation,
                    $direction,
                    $jobGrade->name,
                    number_format($jobGrade->min_salary, 2),
                    number_format($jobGrade->max_salary, 2)
                );

                // Determine severity based on deviation
                $severity = $this->determineSeverityByDeviation($percentageDeviation);

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staffMember->id,
                    'discrepancy_type' => DiscrepancyType::SalaryAnomaly,
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
     * Detect sudden payment spikes compared to historical average.
     */
    protected function detectPaymentSpikes(): int
    {
        $count = 0;

        // Get staff with sufficient payment history
        $staffWithPayments = MonthlyPayment::select('staff_id')
            ->where('payment_date', '>=', now()->subMonths($this->minPaymentHistoryMonths + 1))
            ->groupBy('staff_id')
            ->havingRaw('COUNT(*) >= ?', [$this->minPaymentHistoryMonths])
            ->pluck('staff_id');

        foreach ($staffWithPayments as $staffId) {
            // Get recent payments ordered by date
            $recentPayments = MonthlyPayment::where('staff_id', $staffId)
                ->where('payment_date', '>=', now()->subMonths($this->minPaymentHistoryMonths + 1))
                ->orderBy('payment_date', 'desc')
                ->get();

            if ($recentPayments->count() < $this->minPaymentHistoryMonths) {
                continue;
            }

            // Get latest payment
            $latestPayment = $recentPayments->first();

            // Calculate average of previous payments (excluding latest)
            $previousPayments = $recentPayments->skip(1);
            $averagePreviousPayment = $previousPayments->avg('net_amount');

            if ($averagePreviousPayment <= 0) {
                continue;
            }

            // Calculate percentage increase
            $percentageIncrease = (($latestPayment->net_amount - $averagePreviousPayment) / $averagePreviousPayment) * 100;

            // Check if increase exceeds threshold
            if ($percentageIncrease > $this->paymentSpikeThresholdPercent) {
                // Check if discrepancy already exists for this payment period
                if ($this->discrepancyExistsForMonth($staffId, $latestPayment->payment_month)) {
                    continue;
                }

                $staff = Staff::find($staffId);

                if (! $staff) {
                    continue;
                }

                $description = sprintf(
                    'Staff member received a payment of GHS %s in %s, which is %.1f%% higher than their average payment of GHS %s over the previous %d months. This sudden spike requires investigation.',
                    number_format($latestPayment->net_amount, 2),
                    $latestPayment->payment_month->format('F Y'),
                    $percentageIncrease,
                    number_format($averagePreviousPayment, 2),
                    $previousPayments->count()
                );

                // Determine severity based on spike percentage
                $severity = $this->determineSeverityByDeviation($percentageIncrease);

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staffId,
                    'discrepancy_type' => DiscrepancyType::SalaryAnomaly,
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
     * Detect suspicious duplicate payment amounts across different staff.
     */
    protected function detectSuspiciousDuplicatePayments(): int
    {
        $count = 0;

        // Get recent payments grouped by amount
        $duplicateAmounts = MonthlyPayment::select('net_amount', DB::raw('COUNT(DISTINCT staff_id) as staff_count'))
            ->where('payment_date', '>=', now()->subMonths(3))
            ->groupBy('net_amount')
            ->havingRaw('COUNT(DISTINCT staff_id) >= 5') // At least 5 different staff with same amount
            ->havingRaw('net_amount > 0')
            ->get();

        foreach ($duplicateAmounts as $duplicate) {
            // Get staff IDs with this payment amount
            $staffIds = MonthlyPayment::where('net_amount', $duplicate->net_amount)
                ->where('payment_date', '>=', now()->subMonths(3))
                ->distinct('staff_id')
                ->pluck('staff_id');

            foreach ($staffIds as $staffId) {
                // Check if discrepancy already exists
                if ($this->discrepancyExists($staffId, DiscrepancyType::SalaryAnomaly)) {
                    continue;
                }

                $staff = Staff::find($staffId);

                if (! $staff) {
                    continue;
                }

                $description = sprintf(
                    'Staff member received a payment of exactly GHS %s, which is identical to payments received by %d other staff members. This pattern may indicate systematic error, template usage, or fraudulent activity requiring investigation.',
                    number_format($duplicate->net_amount, 2),
                    $duplicate->staff_count - 1
                );

                // Severity based on number of duplicates
                $severity = $duplicate->staff_count >= 10 ? Severity::High : Severity::Medium;

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staffId,
                    'discrepancy_type' => DiscrepancyType::SalaryAnomaly,
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
     * Detect mismatches between expected salary and actual payments.
     */
    protected function detectPaymentSalaryMismatch(): int
    {
        $count = 0;

        // Get recent payments where net_amount differs significantly from current_salary
        $recentPayments = MonthlyPayment::with('staff')
            ->where('payment_date', '>=', now()->subMonths(3))
            ->get();

        foreach ($recentPayments as $payment) {
            $staff = $payment->staff;

            if (! $staff || ! $staff->current_salary) {
                continue;
            }

            $expectedSalary = (float) $staff->current_salary;
            $actualPayment = (float) $payment->net_amount;

            // Calculate percentage difference
            $percentageDifference = abs((($actualPayment - $expectedSalary) / $expectedSalary) * 100);

            // Check if difference exceeds threshold (30% for this check)
            if ($percentageDifference > 30.0) {
                // Check if discrepancy already exists for this month
                if ($this->discrepancyExistsForMonth($staff->id, $payment->payment_month)) {
                    continue;
                }

                $direction = $actualPayment > $expectedSalary ? 'higher' : 'lower';

                $description = sprintf(
                    'Staff member received a payment of GHS %s in %s, which is %.1f%% %s than their expected salary of GHS %s. This significant mismatch requires verification.',
                    number_format($actualPayment, 2),
                    $payment->payment_month->format('F Y'),
                    $percentageDifference,
                    $direction,
                    number_format($expectedSalary, 2)
                );

                $severity = $percentageDifference > 50.0 ? Severity::High : Severity::Medium;

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staff->id,
                    'discrepancy_type' => DiscrepancyType::SalaryAnomaly,
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
     * Determine severity based on percentage deviation.
     */
    protected function determineSeverityByDeviation(float $percentageDeviation): Severity
    {
        $absDeviation = abs($percentageDeviation);

        if ($absDeviation > 100) {
            return Severity::Critical; // >100% deviation
        } elseif ($absDeviation > 50) {
            return Severity::High; // >50% deviation
        } elseif ($absDeviation > 30) {
            return Severity::Medium; // >30% deviation
        } else {
            return Severity::Low; // 20-30% deviation
        }
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
     * Check if discrepancy already exists for staff in specific month.
     */
    protected function discrepancyExistsForMonth(int $staffId, $paymentMonth): bool
    {
        // Check if salary anomaly discrepancy was created around this payment month
        return Discrepancy::where('staff_id', $staffId)
            ->where('discrepancy_type', DiscrepancyType::SalaryAnomaly)
            ->where('detected_at', '>=', $paymentMonth->copy()->startOfMonth())
            ->where('detected_at', '<=', $paymentMonth->copy()->endOfMonth())
            ->where('status', '!=', DiscrepancyStatus::Dismissed)
            ->exists();
    }

    /**
     * Get salary anomaly statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_salary_anomalies' => Discrepancy::where('discrepancy_type', DiscrepancyType::SalaryAnomaly)->count(),
            'open_salary_anomalies' => Discrepancy::where('discrepancy_type', DiscrepancyType::SalaryAnomaly)
                ->where('status', DiscrepancyStatus::Open)
                ->count(),
            'critical_salary_anomalies' => Discrepancy::where('discrepancy_type', DiscrepancyType::SalaryAnomaly)
                ->where('severity', Severity::Critical)
                ->where('status', '!=', DiscrepancyStatus::Dismissed)
                ->count(),
            'staff_with_salary_anomalies' => Discrepancy::where('discrepancy_type', DiscrepancyType::SalaryAnomaly)
                ->where('status', '!=', DiscrepancyStatus::Dismissed)
                ->distinct('staff_id')
                ->count('staff_id'),
        ];
    }
}
