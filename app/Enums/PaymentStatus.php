<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Processing => 'Processing',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Approved => 'blue',
            self::Processing => 'yellow',
            self::Paid => 'green',
            self::Failed => 'red',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::Paid, self::Failed]);
    }
}
