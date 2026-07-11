<?php

namespace App\Enums;

enum SyncOperationStatus: string
{
    case Applied = 'applied';
    case Conflict = 'conflict';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Conflict => 'Conflict',
            self::Failed => 'Failed',
        };
    }
}
