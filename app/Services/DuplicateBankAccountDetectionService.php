<?php

namespace App\Services;

use App\Enums\DiscrepancyStatus;
use App\Enums\DiscrepancyType;
use App\Enums\Severity;
use App\Models\BankDetail;
use App\Models\Discrepancy;
use App\Models\Staff;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class DuplicateBankAccountDetectionService
{
    /**
     * Run duplicate bank account detection.
     *
     * Returns count of new discrepancies created.
     */
    public function detect(): int
    {
        $detectedCount = 0;

        Log::info('Starting duplicate bank account detection');

        // Get all bank details
        $bankDetails = BankDetail::with('staff')->get();

        // Group by decrypted account number
        $accountGroups = [];

        foreach ($bankDetails as $bankDetail) {
            try {
                $decryptedAccount = Crypt::decryptString($bankDetail->account_number);

                if (! isset($accountGroups[$decryptedAccount])) {
                    $accountGroups[$decryptedAccount] = [];
                }

                $accountGroups[$decryptedAccount][] = $bankDetail;
            } catch (\Exception $e) {
                Log::warning("Failed to decrypt account number for bank_detail ID {$bankDetail->id}");

                continue;
            }
        }

        // Find duplicates (accounts with more than one staff member)
        foreach ($accountGroups as $accountNumber => $details) {
            if (count($details) <= 1) {
                continue; // Not a duplicate
            }

            // We have multiple staff using the same account
            $staffIds = collect($details)->pluck('staff_id')->toArray();
            $staffNames = collect($details)->pluck('staff.full_name')->toArray();
            $bankName = $details[0]->bank->name ?? 'Unknown Bank';

            // Create discrepancy for each staff member involved
            foreach ($staffIds as $staffId) {
                // Check if discrepancy already exists
                if ($this->discrepancyExists($staffId, DiscrepancyType::DuplicateBankAccount)) {
                    continue;
                }

                $otherStaffNames = array_filter($staffNames, function ($name) use ($staffId, $details) {
                    $detail = collect($details)->firstWhere('staff_id', $staffId);

                    return $name !== ($detail->staff->full_name ?? '');
                });

                $description = sprintf(
                    'Staff member shares bank account ****%s at %s with %d other staff member(s): %s. This is a critical fraud indicator. All staff sharing this account should be investigated immediately.',
                    substr($accountNumber, -4),
                    $bankName,
                    count($otherStaffNames),
                    implode(', ', $otherStaffNames)
                );

                // Create discrepancy
                Discrepancy::create([
                    'staff_id' => $staffId,
                    'discrepancy_type' => DiscrepancyType::DuplicateBankAccount,
                    'severity' => Severity::Critical,
                    'status' => DiscrepancyStatus::Open,
                    'description' => $description,
                    'detected_by' => 1,
                    'detected_at' => now(),
                ]);

                $detectedCount++;
            }
        }

        Log::info("Duplicate bank account detection completed. Found {$detectedCount} new discrepancies");

        return $detectedCount;
    }

    /**
     * Detect duplicate mobile money accounts (if applicable).
     */
    public function detectDuplicateMobileMoney(): int
    {
        $detectedCount = 0;

        Log::info('Starting duplicate mobile money account detection');

        // Get all bank details with mobile money
        $mobileMoney = BankDetail::whereNotNull('mobile_money_number')
            ->with('staff')
            ->get();

        // Group by mobile money number
        $mobileMoneyGroups = [];

        foreach ($mobileMoney as $bankDetail) {
            $mobileNumber = $bankDetail->mobile_money_number;

            if (! isset($mobileMoneyGroups[$mobileNumber])) {
                $mobileMoneyGroups[$mobileNumber] = [];
            }

            $mobileMoneyGroups[$mobileNumber][] = $bankDetail;
        }

        // Find duplicates
        foreach ($mobileMoneyGroups as $mobileNumber => $details) {
            if (count($details) <= 1) {
                continue;
            }

            $staffIds = collect($details)->pluck('staff_id')->toArray();
            $staffNames = collect($details)->pluck('staff.full_name')->toArray();

            // Create discrepancy for each staff member
            foreach ($staffIds as $staffId) {
                if ($this->discrepancyExists($staffId, DiscrepancyType::DuplicateBankAccount)) {
                    continue;
                }

                $otherStaffNames = array_filter($staffNames, function ($name) use ($staffId, $details) {
                    $detail = collect($details)->firstWhere('staff_id', $staffId);

                    return $name !== ($detail->staff->full_name ?? '');
                });

                $description = sprintf(
                    'Staff member shares mobile money number %s with %d other staff member(s): %s. This requires immediate investigation.',
                    $mobileNumber,
                    count($otherStaffNames),
                    implode(', ', $otherStaffNames)
                );

                Discrepancy::create([
                    'staff_id' => $staffId,
                    'discrepancy_type' => DiscrepancyType::DuplicateBankAccount,
                    'severity' => Severity::Critical,
                    'status' => DiscrepancyStatus::Open,
                    'description' => $description,
                    'detected_by' => 1,
                    'detected_at' => now(),
                ]);

                $detectedCount++;
            }
        }

        Log::info("Duplicate mobile money detection completed. Found {$detectedCount} new discrepancies");

        return $detectedCount;
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
     * Get duplicate bank account statistics.
     */
    public function getStatistics(): array
    {
        // Count unique account numbers that are duplicated
        $bankDetails = BankDetail::all();
        $accountGroups = [];

        foreach ($bankDetails as $bankDetail) {
            try {
                $decryptedAccount = Crypt::decryptString($bankDetail->account_number);
                if (! isset($accountGroups[$decryptedAccount])) {
                    $accountGroups[$decryptedAccount] = 0;
                }
                $accountGroups[$decryptedAccount]++;
            } catch (\Exception $e) {
                continue;
            }
        }

        $duplicateAccounts = collect($accountGroups)->filter(fn ($count) => $count > 1)->count();

        return [
            'total_duplicate_account_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::DuplicateBankAccount)->count(),
            'open_duplicate_account_discrepancies' => Discrepancy::where('discrepancy_type', DiscrepancyType::DuplicateBankAccount)
                ->where('status', DiscrepancyStatus::Open)
                ->count(),
            'unique_duplicate_accounts' => $duplicateAccounts,
            'staff_with_duplicate_accounts' => Discrepancy::where('discrepancy_type', DiscrepancyType::DuplicateBankAccount)
                ->where('status', '!=', DiscrepancyStatus::Dismissed)
                ->distinct('staff_id')
                ->count('staff_id'),
        ];
    }
}
