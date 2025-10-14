<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\Staff;
use App\Models\Station;
use App\Models\Unit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DataValidationService
{
    protected array $errors = [];

    protected array $lookupCache = [
        'departments' => [],
        'units' => [],
        'job_titles' => [],
        'stations' => [],
        'banks' => [],
        'staff' => [],
    ];

    /**
     * Validate staff import row.
     */
    public function validateStaffRow(array $data, int $rowNumber): array
    {
        $errors = [];

        $validator = Validator::make($data, [
            'staff_number' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'national_id' => ['required', 'string', 'max:50'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_primary' => ['required', 'string', 'max:20'],
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'date_of_hire' => ['required', 'date', 'before_or_equal:today'],
            'employment_status' => ['required', Rule::in(['active', 'on_leave', 'suspended', 'terminated'])],
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'temporary', 'intern'])],
            'current_salary' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => $field,
                    'error_message' => implode(', ', $messages),
                ];
            }
        }

        // Check for duplicate staff_number
        if (isset($data['staff_number']) && $this->isDuplicateStaffNumber($data['staff_number'])) {
            $errors[] = [
                'row_number' => $rowNumber,
                'field_name' => 'staff_number',
                'error_message' => "Staff number {$data['staff_number']} already exists in the database.",
            ];
        }

        // Check for duplicate national_id
        if (isset($data['national_id']) && $this->isDuplicateNationalId($data['national_id'])) {
            $errors[] = [
                'row_number' => $rowNumber,
                'field_name' => 'national_id',
                'error_message' => "National ID {$data['national_id']} already exists in the database.",
            ];
        }

        // Validate foreign keys if provided as names instead of IDs
        if (isset($data['department_id']) && ! is_numeric($data['department_id'])) {
            $departmentId = $this->findDepartmentByName($data['department_id']);
            if (! $departmentId) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => 'department_id',
                    'error_message' => "Department '{$data['department_id']}' not found.",
                ];
            }
        }

        if (isset($data['unit_id']) && ! is_numeric($data['unit_id'])) {
            $unitId = $this->findUnitByName($data['unit_id']);
            if (! $unitId) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => 'unit_id',
                    'error_message' => "Unit '{$data['unit_id']}' not found.",
                ];
            }
        }

        if (isset($data['job_title_id']) && ! is_numeric($data['job_title_id'])) {
            $jobTitleId = $this->findJobTitleByName($data['job_title_id']);
            if (! $jobTitleId) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => 'job_title_id',
                    'error_message' => "Job title '{$data['job_title_id']}' not found.",
                ];
            }
        }

        if (isset($data['station_id']) && ! is_numeric($data['station_id'])) {
            $stationId = $this->findStationByName($data['station_id']);
            if (! $stationId) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => 'station_id',
                    'error_message' => "Station '{$data['station_id']}' not found.",
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate bank details import row.
     */
    public function validateBankDetailsRow(array $data, int $rowNumber): array
    {
        $errors = [];

        $validator = Validator::make($data, [
            'staff_number' => ['required', 'string'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(['savings', 'current', 'checking'])],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => $field,
                    'error_message' => implode(', ', $messages),
                ];
            }
        }

        // Check if staff exists
        if (isset($data['staff_number']) && ! $this->staffExists($data['staff_number'])) {
            $errors[] = [
                'row_number' => $rowNumber,
                'field_name' => 'staff_number',
                'error_message' => "Staff number {$data['staff_number']} not found in the database.",
            ];
        }

        // Check for duplicate account number
        if (isset($data['account_number']) && $this->isDuplicateAccountNumber($data['account_number'])) {
            $errors[] = [
                'row_number' => $rowNumber,
                'field_name' => 'account_number',
                'error_message' => "Account number {$data['account_number']} already exists in the database.",
            ];
        }

        // Validate bank if provided as name instead of ID
        if (isset($data['bank_name']) && ! is_numeric($data['bank_name'])) {
            $bankId = $this->findBankByName($data['bank_name']);
            if (! $bankId) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => 'bank_name',
                    'error_message' => "Bank '{$data['bank_name']}' not found.",
                ];
            }
        }

        return $errors;
    }

    /**
     * Validate monthly payment import row.
     */
    public function validateMonthlyPaymentRow(array $data, int $rowNumber): array
    {
        $errors = [];

        $validator = Validator::make($data, [
            'staff_number' => ['required', 'string'],
            'payment_month' => ['required', 'integer', 'between:1,12'],
            'payment_year' => ['required', 'integer', 'between:2000,2100'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'bonuses' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'net_salary' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => $field,
                    'error_message' => implode(', ', $messages),
                ];
            }
        }

        // Check if staff exists
        if (isset($data['staff_number']) && ! $this->staffExists($data['staff_number'])) {
            $errors[] = [
                'row_number' => $rowNumber,
                'field_name' => 'staff_number',
                'error_message' => "Staff number {$data['staff_number']} not found in the database.",
            ];
        }

        // Validate payment calculation
        if (isset($data['basic_salary'], $data['allowances'], $data['bonuses'], $data['deductions'], $data['tax'], $data['net_salary'])) {
            $calculatedNet = ($data['basic_salary'] + ($data['allowances'] ?? 0) + ($data['bonuses'] ?? 0))
                           - ($data['deductions'] ?? 0) - $data['tax'];

            $difference = abs($calculatedNet - $data['net_salary']);

            if ($difference > 0.01) { // Allow for rounding differences
                $errors[] = [
                    'row_number' => $rowNumber,
                    'field_name' => 'net_salary',
                    'error_message' => "Net salary calculation mismatch. Expected {$calculatedNet}, got {$data['net_salary']}.",
                ];
            }
        }

        return $errors;
    }

    /**
     * Check if staff number already exists.
     */
    protected function isDuplicateStaffNumber(string $staffNumber): bool
    {
        if (! isset($this->lookupCache['staff'][$staffNumber])) {
            $this->lookupCache['staff'][$staffNumber] = Staff::where('staff_number', $staffNumber)->exists();
        }

        return $this->lookupCache['staff'][$staffNumber];
    }

    /**
     * Check if national ID already exists.
     */
    protected function isDuplicateNationalId(string $nationalId): bool
    {
        return Staff::where('national_id', $nationalId)->exists();
    }

    /**
     * Check if account number already exists.
     */
    protected function isDuplicateAccountNumber(string $accountNumber): bool
    {
        return \App\Models\BankDetail::where('account_number', $accountNumber)->exists();
    }

    /**
     * Check if staff exists by staff number.
     */
    protected function staffExists(string $staffNumber): bool
    {
        if (! isset($this->lookupCache['staff'][$staffNumber])) {
            $this->lookupCache['staff'][$staffNumber] = Staff::where('staff_number', $staffNumber)->exists();
        }

        return $this->lookupCache['staff'][$staffNumber];
    }

    /**
     * Find department ID by name.
     */
    protected function findDepartmentByName(string $name): ?int
    {
        if (! isset($this->lookupCache['departments'][$name])) {
            $department = Department::where('name', 'like', "%{$name}%")->first();
            $this->lookupCache['departments'][$name] = $department?->id;
        }

        return $this->lookupCache['departments'][$name];
    }

    /**
     * Find unit ID by name.
     */
    protected function findUnitByName(string $name): ?int
    {
        if (! isset($this->lookupCache['units'][$name])) {
            $unit = Unit::where('name', 'like', "%{$name}%")->first();
            $this->lookupCache['units'][$name] = $unit?->id;
        }

        return $this->lookupCache['units'][$name];
    }

    /**
     * Find job title ID by name.
     */
    protected function findJobTitleByName(string $name): ?int
    {
        if (! isset($this->lookupCache['job_titles'][$name])) {
            $jobTitle = JobTitle::where('title', 'like', "%{$name}%")->first();
            $this->lookupCache['job_titles'][$name] = $jobTitle?->id;
        }

        return $this->lookupCache['job_titles'][$name];
    }

    /**
     * Find station ID by name.
     */
    protected function findStationByName(string $name): ?int
    {
        if (! isset($this->lookupCache['stations'][$name])) {
            $station = Station::where('name', 'like', "%{$name}%")->first();
            $this->lookupCache['stations'][$name] = $station?->id;
        }

        return $this->lookupCache['stations'][$name];
    }

    /**
     * Find bank ID by name.
     */
    protected function findBankByName(string $name): ?int
    {
        if (! isset($this->lookupCache['banks'][$name])) {
            $bank = Bank::where('name', 'like', "%{$name}%")->first();
            $this->lookupCache['banks'][$name] = $bank?->id;
        }

        return $this->lookupCache['banks'][$name];
    }

    /**
     * Resolve foreign key references (convert names to IDs).
     */
    public function resolveForeignKeys(array $data, string $importType): array
    {
        if ($importType === 'staff') {
            if (isset($data['department_id']) && ! is_numeric($data['department_id'])) {
                $data['department_id'] = $this->findDepartmentByName($data['department_id']);
            }

            if (isset($data['unit_id']) && ! is_numeric($data['unit_id'])) {
                $data['unit_id'] = $this->findUnitByName($data['unit_id']);
            }

            if (isset($data['job_title_id']) && ! is_numeric($data['job_title_id'])) {
                $data['job_title_id'] = $this->findJobTitleByName($data['job_title_id']);
            }

            if (isset($data['station_id']) && ! is_numeric($data['station_id'])) {
                $data['station_id'] = $this->findStationByName($data['station_id']);
            }
        }

        if ($importType === 'bank_details') {
            if (isset($data['bank_name']) && ! is_numeric($data['bank_name'])) {
                $data['bank_id'] = $this->findBankByName($data['bank_name']);
                unset($data['bank_name']);
            }

            if (isset($data['staff_number'])) {
                $staff = Staff::where('staff_number', $data['staff_number'])->first();
                $data['staff_id'] = $staff?->id;
                unset($data['staff_number']);
            }
        }

        if ($importType === 'monthly_payments') {
            if (isset($data['staff_number'])) {
                $staff = Staff::where('staff_number', $data['staff_number'])->first();
                $data['staff_id'] = $staff?->id;
                unset($data['staff_number']);
            }
        }

        return $data;
    }
}
