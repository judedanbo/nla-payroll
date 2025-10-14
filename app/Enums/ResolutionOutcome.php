<?php

namespace App\Enums;

enum ResolutionOutcome: string
{
    case Resolved = 'resolved';
    case PartiallyResolved = 'partially_resolved';
    case Unresolved = 'unresolved';

    public function label(): string
    {
        return match ($this) {
            self::Resolved => 'Resolved',
            self::PartiallyResolved => 'Partially Resolved',
            self::Unresolved => 'Unresolved',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Resolved => 'green',
            self::PartiallyResolved => 'yellow',
            self::Unresolved => 'red',
        };
    }
}
