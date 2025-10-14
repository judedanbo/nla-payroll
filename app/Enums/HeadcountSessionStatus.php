<?php

namespace App\Enums;

enum HeadcountSessionStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Paused = 'paused';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Paused => 'Paused',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::InProgress => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'red',
            self::Paused => 'yellow',
        };
    }

    public function isActive(): bool
    {
        return $this === self::InProgress;
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Planned => in_array($status, [self::InProgress, self::Cancelled]),
            self::InProgress => in_array($status, [self::Completed, self::Cancelled]),
            self::Completed, self::Cancelled => false,
            self::Paused => in_array($status, [self::InProgress, self::Cancelled]),
        };
    }
}
