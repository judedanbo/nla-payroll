<?php

namespace App\Enums;

enum DiscrepancyType: string
{
    case GhostEmployee = 'ghost_employee';
    case DuplicateBankAccount = 'duplicate_bank_account';
    case StationMismatch = 'station_mismatch';
    case SalaryAnomaly = 'salary_anomaly';
    case MissingData = 'missing_data';
    case UnregisteredPersonnel = 'unregistered_personnel';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GhostEmployee => 'Ghost Employee',
            self::DuplicateBankAccount => 'Duplicate Bank Account',
            self::StationMismatch => 'Station Mismatch',
            self::SalaryAnomaly => 'Salary Anomaly',
            self::MissingData => 'Missing Data',
            self::UnregisteredPersonnel => 'Unregistered Personnel',
            self::Other => 'Other',
        };
    }

    public function defaultSeverity(): Severity
    {
        return match ($this) {
            self::GhostEmployee, self::DuplicateBankAccount => Severity::Critical,
            self::SalaryAnomaly, self::StationMismatch => Severity::High,
            self::MissingData, self::UnregisteredPersonnel => Severity::Medium,
            self::Other => Severity::Low,
        };
    }
}
