<?php

namespace App\DTO\Sync;

use App\Enums\SyncOperationStatus;

readonly class SyncResult
{
    public function __construct(
        public SyncOperationStatus $status,
        public ?string $serverId = null,
        public array $errors = [],
    ) {}

    public static function applied(?string $serverId = null): self
    {
        return new self(SyncOperationStatus::Applied, $serverId);
    }

    public static function conflict(array $errors): self
    {
        return new self(SyncOperationStatus::Conflict, errors: $errors);
    }

    public static function failed(array $errors): self
    {
        return new self(SyncOperationStatus::Failed, errors: $errors);
    }
}
