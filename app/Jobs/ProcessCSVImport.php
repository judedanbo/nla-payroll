<?php

namespace App\Jobs;

use App\Models\BankDetail;
use App\Models\ImportedRecord;
use App\Models\ImportError;
use App\Models\ImportHistory;
use App\Models\MonthlyPayment;
use App\Models\Staff;
use App\Services\CSVImportService;
use App\Services\DataValidationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCSVImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600; // 1 hour timeout

    public int $tries = 1; // Don't retry failed imports

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ImportHistory $importHistory,
        public array $columnMapping
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        CSVImportService $csvService,
        DataValidationService $validationService
    ): void {
        try {
            $this->importHistory->update(['status' => 'processing']);

            $filePath = storage_path("app/{$this->importHistory->file_path}");

            // Get total rows for progress tracking
            $totalRows = $csvService->getTotalRows($filePath);
            $this->importHistory->update(['total_records' => $totalRows]);

            $successCount = 0;
            $failureCount = 0;

            // Process in chunks
            foreach ($csvService->processInChunks($filePath, $this->columnMapping, 500) as $chunk) {
                DB::transaction(function () use ($chunk, $validationService, &$successCount, &$failureCount) {
                    foreach ($chunk as $row) {
                        $rowNumber = $row['_row_number'];
                        unset($row['_row_number']);

                        // Validate row based on import type
                        $errors = $this->validateRow($validationService, $row, $rowNumber);

                        if (! empty($errors)) {
                            // Log errors
                            foreach ($errors as $error) {
                                ImportError::create([
                                    'import_history_id' => $this->importHistory->id,
                                    'row_number' => $error['row_number'],
                                    'field_name' => $error['field_name'],
                                    'error_message' => $error['error_message'],
                                    'row_data' => $row,
                                ]);
                            }

                            $failureCount++;

                            continue;
                        }

                        // Resolve foreign keys (convert names to IDs)
                        $row = $validationService->resolveForeignKeys($row, $this->importHistory->import_type);

                        // Import row
                        $record = $this->importRow($row);

                        if ($record) {
                            // Track imported record for rollback capability
                            ImportedRecord::create([
                                'import_history_id' => $this->importHistory->id,
                                'recordable_type' => get_class($record),
                                'recordable_id' => $record->id,
                            ]);

                            $successCount++;
                        } else {
                            $failureCount++;
                        }
                    }
                });

                // Update progress
                $this->importHistory->update([
                    'successful_records' => $successCount,
                    'failed_records' => $failureCount,
                ]);
            }

            // Mark import as completed
            $this->importHistory->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info("Import completed: {$successCount} successful, {$failureCount} failed", [
                'import_id' => $this->importHistory->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Import failed: '.$e->getMessage(), [
                'import_id' => $this->importHistory->id,
                'exception' => $e,
            ]);

            $this->importHistory->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate a single row based on import type.
     */
    protected function validateRow(DataValidationService $service, array $data, int $rowNumber): array
    {
        return match ($this->importHistory->import_type) {
            'staff' => $service->validateStaffRow($data, $rowNumber),
            'bank_details' => $service->validateBankDetailsRow($data, $rowNumber),
            'monthly_payments' => $service->validateMonthlyPaymentRow($data, $rowNumber),
            default => [],
        };
    }

    /**
     * Import a single row based on import type.
     */
    protected function importRow(array $data): ?object
    {
        try {
            return match ($this->importHistory->import_type) {
                'staff' => $this->importStaff($data),
                'bank_details' => $this->importBankDetail($data),
                'monthly_payments' => $this->importMonthlyPayment($data),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Failed to import row: '.$e->getMessage(), [
                'import_type' => $this->importHistory->import_type,
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * Import staff record.
     */
    protected function importStaff(array $data): Staff
    {
        return Staff::create($data);
    }

    /**
     * Import bank detail record.
     */
    protected function importBankDetail(array $data): BankDetail
    {
        return BankDetail::create($data);
    }

    /**
     * Import monthly payment record.
     */
    protected function importMonthlyPayment(array $data): MonthlyPayment
    {
        return MonthlyPayment::create($data);
    }
}
