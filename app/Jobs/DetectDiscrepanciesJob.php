<?php

namespace App\Jobs;

use App\Services\DuplicateBankAccountDetectionService;
use App\Services\GhostEmployeeDetectionService;
use App\Services\SalaryAnomalyDetectionService;
use App\Services\StationMismatchDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DetectDiscrepanciesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour timeout

    public int $tries = 1; // Don't retry - will run again on next schedule

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     *
     * Runs all discrepancy detection services and logs results.
     */
    public function handle(
        GhostEmployeeDetectionService $ghostDetection,
        StationMismatchDetectionService $stationDetection,
        DuplicateBankAccountDetectionService $duplicateAccountDetection,
        SalaryAnomalyDetectionService $salaryAnomalyDetection
    ): void {
        try {
            Log::info('Starting scheduled discrepancy detection');

            $totalDetected = 0;

            // Run Ghost Employee Detection
            $ghostCount = $ghostDetection->detect();
            $totalDetected += $ghostCount;
            Log::info("Ghost employee detection: {$ghostCount} new discrepancies");

            // Run Station Mismatch Detection
            $stationCount = $stationDetection->detect();
            $totalDetected += $stationCount;
            Log::info("Station mismatch detection: {$stationCount} new discrepancies");

            // Run Duplicate Bank Account Detection
            $duplicateCount = $duplicateAccountDetection->detect();
            $totalDetected += $duplicateCount;
            Log::info("Duplicate bank account detection: {$duplicateCount} new discrepancies");

            // Also check mobile money duplicates
            $mobileMoneyCount = $duplicateAccountDetection->detectDuplicateMobileMoney();
            $totalDetected += $mobileMoneyCount;
            Log::info("Duplicate mobile money detection: {$mobileMoneyCount} new discrepancies");

            // Run Salary Anomaly Detection
            $salaryAnomalyCount = $salaryAnomalyDetection->detect();
            $totalDetected += $salaryAnomalyCount;
            Log::info("Salary anomaly detection: {$salaryAnomalyCount} new discrepancies");

            Log::info("Discrepancy detection completed. Total new discrepancies: {$totalDetected}");

            // Get summary statistics
            $this->logStatistics(
                $ghostDetection,
                $stationDetection,
                $duplicateAccountDetection,
                $salaryAnomalyDetection
            );
        } catch (\Exception $e) {
            Log::error('Discrepancy detection job failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Log summary statistics from all detection services.
     */
    protected function logStatistics(
        GhostEmployeeDetectionService $ghostDetection,
        StationMismatchDetectionService $stationDetection,
        DuplicateBankAccountDetectionService $duplicateAccountDetection,
        SalaryAnomalyDetectionService $salaryAnomalyDetection
    ): void {
        $ghostStats = $ghostDetection->getStatistics();
        $stationStats = $stationDetection->getStatistics();
        $duplicateStats = $duplicateAccountDetection->getStatistics();
        $salaryStats = $salaryAnomalyDetection->getStatistics();

        Log::info('Discrepancy Detection Summary:', [
            'ghost_employees' => $ghostStats,
            'station_mismatches' => $stationStats,
            'duplicate_accounts' => $duplicateStats,
            'salary_anomalies' => $salaryStats,
        ]);
    }
}
