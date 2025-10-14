<?php

namespace App\Enums;

enum PaymentElementType: string
{
    case BasicSalary = 'basic_salary';
    case Allowance = 'allowance';
    case Deduction = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::BasicSalary => 'Basic Salary',
            self::Allowance => 'Allowance',
            self::Deduction => 'Deduction',
        };
    }

    public function multiplier(): int
    {
        return match ($this) {
            self::BasicSalary, self::Allowance => 1,
            self::Deduction => -1,
        };
    }
}
