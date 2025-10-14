<?php

namespace App\Enums;

enum ImportType: string
{
    case Staff = 'staff';
    case BankDetails = 'bank_details';
    case Payroll = 'payroll';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff Records',
            self::BankDetails => 'Bank Details',
            self::Payroll => 'Payroll Data',
        };
    }

    public function expectedColumns(): array
    {
        return match ($this) {
            self::Staff => [
                'staff_number', 'full_name', 'date_of_birth', 'gender',
                'email', 'phone_primary', 'department', 'unit', 'job_title',
                'station', 'employment_status', 'employment_type', 'current_salary',
            ],
            self::BankDetails => [
                'staff_number', 'bank_name', 'account_number', 'account_name',
                'branch', 'is_primary',
            ],
            self::Payroll => [
                'staff_number', 'payment_month', 'basic_salary', 'allowances',
                'deductions', 'net_pay', 'payment_date', 'payment_status',
            ],
        };
    }
}
