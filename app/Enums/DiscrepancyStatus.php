<?php

namespace App\Enums;

enum DiscrepancyStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::UnderReview => 'Under Review',
            self::Resolved => 'Resolved',
            self::Dismissed => 'Dismissed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'red',
            self::UnderReview => 'yellow',
            self::Resolved => 'green',
            self::Dismissed => 'gray',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::UnderReview]);
    }
}
