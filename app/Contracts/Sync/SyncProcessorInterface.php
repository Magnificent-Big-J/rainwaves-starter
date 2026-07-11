<?php

namespace App\Contracts\Sync;

use App\Models\User;

interface SyncProcessorInterface
{
    /**
     * Process a validated batch of operations in order.
     *
     * @param  array<int, array>  $operations  Validated operation payloads
     * @return array<int, array{id: string, status: string, server_id: ?string, errors: object|array}>
     */
    public function process(User $user, array $operations): array;
}
