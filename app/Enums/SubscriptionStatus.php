<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Initiated  = 'initiated';
    case Processing = 'processing';
    case Pending    = 'pending';
    case Active     = 'active';
    case Returned   = 'returned';
    case Cancelled  = 'cancelled';
    case Failed     = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Initiated  => 'Initiated',
            self::Processing => 'Processing',
            self::Pending    => 'Pending',
            self::Active     => 'Active',
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
            self::Active     => 'success',
            self::Returned   => 'success',
            self::Cancelled  => 'default',
            self::Failed     => 'error',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Active, self::Cancelled, self::Failed, self::Returned], true);
    }
}
