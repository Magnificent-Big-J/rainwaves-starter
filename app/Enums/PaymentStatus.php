<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Processing = 'processing';
    case Pending = 'pending';
    case Paid = 'paid';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Initiated  => 'Initiated',
            self::Processing => 'Processing',
            self::Pending    => 'Pending',
            self::Paid       => 'Paid',
            self::Returned   => 'Returned',
            self::Cancelled  => 'Cancelled',
            self::Failed     => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Initiated  => 'info',
            self::Processing => 'warning',
            self::Pending    => 'warning',
            self::Paid       => 'success',
            self::Returned   => 'success',
            self::Cancelled  => 'default',
            self::Failed     => 'error',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled, self::Failed, self::Returned], true);
    }
}
