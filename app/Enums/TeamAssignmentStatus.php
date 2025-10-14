<?php

namespace App\Enums;

enum TeamAssignmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Completed => 'blue',
            self::Cancelled => 'red',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
