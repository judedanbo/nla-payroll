<?php

namespace App\Enums;

enum ResolutionType: string
{
    case Corrected = 'corrected';
    case VerifiedValid = 'verified_valid';
    case StaffRemoved = 'staff_removed';
    case DataUpdated = 'data_updated';
    case NoActionRequired = 'no_action_required';
    case Escalated = 'escalated';

    public function label(): string
    {
        return match ($this) {
            self::Corrected => 'Corrected',
            self::VerifiedValid => 'Verified Valid',
            self::StaffRemoved => 'Staff Removed',
            self::DataUpdated => 'Data Updated',
            self::NoActionRequired => 'No Action Required',
            self::Escalated => 'Escalated',
        };
    }
}
