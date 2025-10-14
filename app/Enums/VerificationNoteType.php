<?php

namespace App\Enums;

enum VerificationNoteType: string
{
    case General = 'general';
    case Discrepancy = 'discrepancy';
    case Concern = 'concern';
    case Clarification = 'clarification';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Discrepancy => 'Discrepancy',
            self::Concern => 'Concern',
            self::Clarification => 'Clarification',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::General => 'gray',
            self::Discrepancy => 'red',
            self::Concern => 'yellow',
            self::Clarification => 'blue',
        };
    }
}
