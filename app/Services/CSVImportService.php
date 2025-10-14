<?php

namespace App\Services;

use Illuminate\Support\Str;

class CSVImportService
{
    /**
     * Preview CSV file - parse headers and first 10 rows.
     */
    public function previewFile(string $filePath): array
    {
        $file = fopen($filePath, 'r');

        if (! $file) {
            throw new \RuntimeException('Unable to open file for reading.');
        }

        // Get headers (first row)
        $headers = fgetcsv($file);

        if (! $headers) {
            fclose($file);
            throw new \RuntimeException('File appears to be empty or invalid.');
        }

        // Get first 10 data rows
        $rows = [];
        $count = 0;
        while (($row = fgetcsv($file)) !== false && $count < 10) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
                $count++;
            }
        }

        fclose($file);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_preview_rows' => count($rows),
        ];
    }

    /**
     * Get expected columns based on import type.
     */
    public function getExpectedColumns(string $importType): array
    {
        return match ($importType) {
            'staff' => [
                'staff_number' => 'Staff Number',
                'first_name' => 'First Name',
                'middle_name' => 'Middle Name (Optional)',
                'last_name' => 'Last Name',
                'date_of_birth' => 'Date of Birth',
                'national_id' => 'National ID',
                'gender' => 'Gender',
                'marital_status' => 'Marital Status',
                'email' => 'Email (Optional)',
                'phone_primary' => 'Primary Phone',
                'phone_secondary' => 'Secondary Phone (Optional)',
                'address' => 'Address',
                'city' => 'City',
                'region' => 'Region',
                'department_id' => 'Department',
                'unit_id' => 'Unit',
                'job_title_id' => 'Job Title',
                'station_id' => 'Station',
                'date_of_hire' => 'Date of Hire',
                'employment_status' => 'Employment Status',
                'employment_type' => 'Employment Type',
                'current_salary' => 'Current Salary',
            ],
            'bank_details' => [
                'staff_number' => 'Staff Number',
                'bank_name' => 'Bank Name',
                'account_number' => 'Account Number',
                'account_name' => 'Account Name',
                'account_type' => 'Account Type',
                'is_primary' => 'Primary Account',
            ],
            'monthly_payments' => [
                'staff_number' => 'Staff Number',
                'payment_month' => 'Payment Month',
                'payment_year' => 'Payment Year',
                'basic_salary' => 'Basic Salary',
                'allowances' => 'Allowances (Optional)',
                'bonuses' => 'Bonuses (Optional)',
                'deductions' => 'Deductions (Optional)',
                'tax' => 'Tax',
                'net_salary' => 'Net Salary',
            ],
            default => throw new \InvalidArgumentException("Invalid import type: {$importType}"),
        };
    }

    /**
     * Auto-map CSV columns to database fields using fuzzy matching.
     */
    public function autoMapColumns(array $csvHeaders, array $expectedColumns): array
    {
        $mapping = [];

        foreach ($csvHeaders as $csvHeader) {
            $normalizedCsvHeader = $this->normalizeColumnName($csvHeader);

            // Try exact match first
            foreach ($expectedColumns as $dbField => $label) {
                if ($this->normalizeColumnName($label) === $normalizedCsvHeader ||
                    $this->normalizeColumnName($dbField) === $normalizedCsvHeader) {
                    $mapping[$csvHeader] = $dbField;
                    break;
                }
            }

            // Try fuzzy match if no exact match
            if (! isset($mapping[$csvHeader])) {
                foreach ($expectedColumns as $dbField => $label) {
                    $normalizedDbField = $this->normalizeColumnName($dbField);
                    $normalizedLabel = $this->normalizeColumnName($label);

                    if (Str::contains($normalizedCsvHeader, $normalizedDbField) ||
                        Str::contains($normalizedDbField, $normalizedCsvHeader) ||
                        Str::contains($normalizedCsvHeader, $normalizedLabel) ||
                        Str::contains($normalizedLabel, $normalizedCsvHeader)) {
                        $mapping[$csvHeader] = $dbField;
                        break;
                    }
                }
            }

            // If still no match, leave unmapped
            if (! isset($mapping[$csvHeader])) {
                $mapping[$csvHeader] = null;
            }
        }

        return $mapping;
    }

    /**
     * Process CSV file in chunks and yield rows.
     */
    public function processInChunks(string $filePath, array $columnMapping, int $chunkSize = 500): \Generator
    {
        $file = fopen($filePath, 'r');

        if (! $file) {
            throw new \RuntimeException('Unable to open file for reading.');
        }

        // Skip header row
        $headers = fgetcsv($file);

        if (! $headers) {
            fclose($file);
            throw new \RuntimeException('File appears to be empty or invalid.');
        }

        $chunk = [];
        $rowNumber = 1; // Start at 1 (after header)

        while (($row = fgetcsv($file)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map CSV row to database fields
            $mappedRow = $this->mapRowData(array_combine($headers, $row), $columnMapping);
            $mappedRow['_row_number'] = $rowNumber;

            $chunk[] = $mappedRow;

            // Yield chunk when it reaches the chunk size
            if (count($chunk) >= $chunkSize) {
                yield $chunk;
                $chunk = [];
            }
        }

        // Yield remaining rows
        if (! empty($chunk)) {
            yield $chunk;
        }

        fclose($file);
    }

    /**
     * Map row data from CSV columns to database fields.
     */
    protected function mapRowData(array $row, array $columnMapping): array
    {
        $mapped = [];

        foreach ($columnMapping as $csvColumn => $dbField) {
            if ($dbField && isset($row[$csvColumn])) {
                $mapped[$dbField] = $this->cleanValue($row[$csvColumn]);
            }
        }

        return $mapped;
    }

    /**
     * Normalize column name for comparison.
     */
    protected function normalizeColumnName(string $name): string
    {
        // Remove special characters, convert to lowercase, remove spaces
        return Str::lower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
    }

    /**
     * Clean and trim value.
     */
    protected function cleanValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            // Convert empty strings to null
            if ($value === '' || $value === 'NULL' || $value === 'null') {
                return null;
            }
        }

        return $value;
    }

    /**
     * Get total row count from CSV file.
     */
    public function getTotalRows(string $filePath): int
    {
        $file = fopen($filePath, 'r');

        if (! $file) {
            throw new \RuntimeException('Unable to open file for reading.');
        }

        $count = 0;
        while (fgets($file) !== false) {
            $count++;
        }

        fclose($file);

        // Subtract 1 for header row
        return max(0, $count - 1);
    }
}
