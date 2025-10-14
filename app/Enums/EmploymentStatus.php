<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::OnLeave => 'On Leave',
            self::Suspended => 'Suspended',
            self::Terminated => 'Terminated',
            self::Retired => 'Retired',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::OnLeave]);
    }
}
