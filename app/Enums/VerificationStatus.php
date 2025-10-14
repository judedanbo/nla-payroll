<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case OnLeave = 'on_leave';
    case Ghost = 'ghost';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::OnLeave => 'On Leave',
            self::Ghost => 'Ghost Employee',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'green',
            self::Absent => 'red',
            self::OnLeave => 'blue',
            self::Ghost => 'purple',
        };
    }

    public function isCritical(): bool
    {
        return in_array($this, [self::Absent, self::Ghost]);
    }
}
