<?php

namespace App\DTO\Sync;

use Carbon\CarbonImmutable;

/**
 * One immutable client operation from the offline queue.
 *
 * `clientId` is the device-generated UUID for creates; `resourceId` targets
 * an existing server record for updates/deletes. `dependsOn` lists op ids
 * that must have been applied before this one may run.
 */
readonly class SyncOperation
{
    public function __construct(
        public string $id,
        public string $type,
        public string $resource,
        public array $payload,
        public CarbonImmutable $occurredAt,
        public ?string $resourceId = null,
        public ?string $clientId = null,
        public array $dependsOn = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            type: $data['type'],
            resource: $data['resource'],
            payload: $data['payload'] ?? [],
            occurredAt: CarbonImmutable::parse($data['occurred_at']),
            resourceId: $data['resource_id'] ?? null,
            clientId: $data['client_id'] ?? null,
            dependsOn: $data['depends_on'] ?? [],
        );
    }
}
