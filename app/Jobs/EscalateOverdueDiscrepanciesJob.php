<?php

namespace App\Jobs;

use App\Enums\DiscrepancyStatus;
use App\Enums\Severity;
use App\Models\Discrepancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EscalateOverdueDiscrepanciesJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800; // 30 minutes timeout

    public int $tries = 1; // Don't retry - will run again on next schedule

    /**
     * Number of days before a discrepancy is considered overdue.
     */
    protected int $overdueDaysThreshold = 7;

    /**
     * Number of days before a critical discrepancy is considered overdue.
     */
    protected int $criticalOverdueDaysThreshold = 3;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     *
     * Escalates discrepancies that remain unresolved for too long.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting overdue discrepancy escalation');

            $escalatedCount = 0;

            // Escalate critical discrepancies overdue for 3+ days
            $escalatedCount += $this->escalateCriticalDiscrepancies();

            // Escalate high priority discrepancies overdue for 7+ days
            $escalatedCount += $this->escalateHighPriorityDiscrepancies();

            // Auto-mark long-overdue open discrepancies as under review
            $reviewCount = $this->markOverdueAsUnderReview();

            Log::info("Escalation completed. {$escalatedCount} discrepancies escalated, {$reviewCount} marked under review");

            // Log summary statistics
            $this->logEscalationStatistics();
        } catch (\Exception $e) {
            Log::error('Discrepancy escalation job failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * Escalate critical discrepancies that are overdue.
     */
    protected function escalateCriticalDiscrepancies(): int
    {
        $cutoffDate = now()->subDays($this->criticalOverdueDaysThreshold);

        $overdueDiscrepancies = Discrepancy::where('severity', Severity::Critical)
            ->whereIn('status', [DiscrepancyStatus::Open, DiscrepancyStatus::UnderReview])
            ->where('detected_at', '<=', $cutoffDate)
            ->get();

        $count = 0;

        foreach ($overdueDiscrepancies as $discrepancy) {
            // Add note about escalation
            $discrepancy->addNote(
                createdBy: 1, // System user
                content: sprintf(
                    'CRITICAL ESCALATION: This critical discrepancy has been open for %d days without resolution. Immediate action required.',
                    $discrepancy->detected_at->diffInDays(now())
                ),
                isInternal: false
            );

            Log::warning('Critical discrepancy escalated', [
                'discrepancy_id' => $discrepancy->id,
                'staff_id' => $discrepancy->staff_id,
                'type' => $discrepancy->discrepancy_type->value,
                'days_open' => $discrepancy->detected_at->diffInDays(now()),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Escalate high priority discrepancies that are overdue.
     */
    protected function escalateHighPriorityDiscrepancies(): int
    {
        $cutoffDate = now()->subDays($this->overdueDaysThreshold);

        $overdueDiscrepancies = Discrepancy::where('severity', Severity::High)
            ->whereIn('status', [DiscrepancyStatus::Open, DiscrepancyStatus::UnderReview])
            ->where('detected_at', '<=', $cutoffDate)
            ->get();

        $count = 0;

        foreach ($overdueDiscrepancies as $discrepancy) {
            // Add note about escalation
            $discrepancy->addNote(
                createdBy: 1, // System user
                content: sprintf(
                    'ESCALATION: This high-priority discrepancy has been open for %d days without resolution. Please prioritize investigation.',
                    $discrepancy->detected_at->diffInDays(now())
                ),
                isInternal: false
            );

            Log::warning('High priority discrepancy escalated', [
                'discrepancy_id' => $discrepancy->id,
                'staff_id' => $discrepancy->staff_id,
                'type' => $discrepancy->discrepancy_type->value,
                'days_open' => $discrepancy->detected_at->diffInDays(now()),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Mark long-overdue open discrepancies as under review.
     */
    protected function markOverdueAsUnderReview(): int
    {
        $cutoffDate = now()->subDays($this->overdueDaysThreshold * 2); // 14 days for open items

        $overdueOpen = Discrepancy::where('status', DiscrepancyStatus::Open)
            ->where('detected_at', '<=', $cutoffDate)
            ->get();

        $count = 0;

        foreach ($overdueOpen as $discrepancy) {
            $discrepancy->markUnderReview();

            $discrepancy->addNote(
                createdBy: 1, // System user
                content: sprintf(
                    'Automatically marked as under review after being open for %d days.',
                    $discrepancy->detected_at->diffInDays(now())
                ),
                isInternal: true
            );

            Log::info('Discrepancy auto-marked under review', [
                'discrepancy_id' => $discrepancy->id,
                'staff_id' => $discrepancy->staff_id,
                'type' => $discrepancy->discrepancy_type->value,
                'days_open' => $discrepancy->detected_at->diffInDays(now()),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Log summary statistics about overdue discrepancies.
     */
    protected function logEscalationStatistics(): void
    {
        $criticalOverdue = Discrepancy::where('severity', Severity::Critical)
            ->whereIn('status', [DiscrepancyStatus::Open, DiscrepancyStatus::UnderReview])
            ->where('detected_at', '<=', now()->subDays($this->criticalOverdueDaysThreshold))
            ->count();

        $highOverdue = Discrepancy::where('severity', Severity::High)
            ->whereIn('status', [DiscrepancyStatus::Open, DiscrepancyStatus::UnderReview])
            ->where('detected_at', '<=', now()->subDays($this->overdueDaysThreshold))
            ->count();

        $allOverdue = Discrepancy::whereIn('status', [DiscrepancyStatus::Open, DiscrepancyStatus::UnderReview])
            ->where('detected_at', '<=', now()->subDays($this->overdueDaysThreshold))
            ->count();

        Log::info('Overdue Discrepancy Statistics:', [
            'critical_overdue_count' => $criticalOverdue,
            'high_overdue_count' => $highOverdue,
            'total_overdue_count' => $allOverdue,
            'critical_threshold_days' => $this->criticalOverdueDaysThreshold,
            'standard_threshold_days' => $this->overdueDaysThreshold,
        ]);
    }
}
