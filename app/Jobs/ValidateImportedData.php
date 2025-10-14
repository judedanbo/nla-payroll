<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Models\Staff;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ValidateImportedData implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800; // 30 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ImportHistory $importHistory
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $anomalies = [];

            // Only validate staff imports for now
            if ($this->importHistory->import_type !== 'staff') {
                return;
            }

            // Get all imported staff from this import
            $importedStaffIds = $this->importHistory->importedRecords()
                ->where('recordable_type', Staff::class)
                ->pluck('recordable_id');

            $staff = Staff::whereIn('id', $importedStaffIds)->get();

            foreach ($staff as $member) {
                // Check for missing bank details
                if (! $member->hasCompleteBankDetails()) {
                    $anomalies[] = [
                        'staff_id' => $member->id,
                        'staff_number' => $member->staff_number,
                        'type' => 'missing_bank_details',
                        'message' => 'Staff member does not have complete bank details',
                    ];
                }

                // Check for salary outside job grade range
                if ($member->jobTitle && $member->jobTitle->jobGrade) {
                    $grade = $member->jobTitle->jobGrade;
                    if ($member->current_salary < $grade->min_salary || $member->current_salary > $grade->max_salary) {
                        $anomalies[] = [
                            'staff_id' => $member->id,
                            'staff_number' => $member->staff_number,
                            'type' => 'salary_out_of_range',
                            'message' => "Salary {$member->current_salary} is outside job grade range ({$grade->min_salary} - {$grade->max_salary})",
                        ];
                    }
                }

                // Check for missing biographical data
                if (! $member->hasSufficientBioData()) {
                    $anomalies[] = [
                        'staff_id' => $member->id,
                        'staff_number' => $member->staff_number,
                        'type' => 'insufficient_bio_data',
                        'message' => 'Staff member has insufficient biographical data',
                    ];
                }

                // Check for age anomalies (under 18 or over 70)
                if ($member->date_of_birth) {
                    $age = now()->diffInYears($member->date_of_birth);
                    if ($age < 18 || $age > 70) {
                        $anomalies[] = [
                            'staff_id' => $member->id,
                            'staff_number' => $member->staff_number,
                            'type' => 'age_anomaly',
                            'message' => "Staff age ({$age}) is outside normal range (18-70)",
                        ];
                    }
                }

                // Check for employment duration anomalies
                if ($member->date_of_hire) {
                    $yearsOfService = now()->diffInYears($member->date_of_hire);
                    if ($yearsOfService > 45) {
                        $anomalies[] = [
                            'staff_id' => $member->id,
                            'staff_number' => $member->staff_number,
                            'type' => 'long_service_anomaly',
                            'message' => "Years of service ({$yearsOfService}) exceeds 45 years",
                        ];
                    }
                }
            }

            // Log anomalies
            if (! empty($anomalies)) {
                Log::warning('Data anomalies detected in import', [
                    'import_id' => $this->importHistory->id,
                    'anomaly_count' => count($anomalies),
                    'anomalies' => $anomalies,
                ]);

                // Store anomalies in import history metadata
                $this->importHistory->update([
                    'options' => array_merge(
                        $this->importHistory->options ?? [],
                        ['anomalies' => $anomalies]
                    ),
                ]);
            }

            Log::info('Post-import validation completed', [
                'import_id' => $this->importHistory->id,
                'records_validated' => $staff->count(),
                'anomalies_found' => count($anomalies),
            ]);
        } catch (\Exception $e) {
            Log::error('Post-import validation failed: '.$e->getMessage(), [
                'import_id' => $this->importHistory->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
