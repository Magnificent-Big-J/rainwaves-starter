<?php

namespace App\Services\Sync;

use App\Contracts\Sync\SyncProcessorInterface;
use App\DTO\Sync\SyncOperation;
use App\DTO\Sync\SyncResult;
use App\Enums\SyncOperationStatus;
use App\Exceptions\SyncConflictException;
use App\Models\SyncOperationRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class SyncProcessor implements SyncProcessorInterface
{
    public function __construct(private SyncRegistry $registry) {}

    public function process(User $user, array $operations): array
    {
        $results = [];
        /** @var array<string, string> $batchStatuses op_id => status value */
        $batchStatuses = [];

        foreach ($operations as $data) {
            $operation = SyncOperation::fromArray($data);

            // Idempotency: a replayed op id returns its stored outcome.
            $existing = SyncOperationRecord::where('op_id', $operation->id)
                ->where('user_id', $user->getKey())
                ->first();

            if ($existing !== null) {
                $batchStatuses[$operation->id] = $existing->status->value;
                $results[] = $existing->toResultArray();

                continue;
            }

            $result = $this->applyOperation($user, $operation, $batchStatuses);

            $record = SyncOperationRecord::create([
                'op_id' => $operation->id,
                'user_id' => $user->getKey(),
                'resource' => $operation->resource,
                'type' => $operation->type,
                'client_id' => $operation->clientId,
                'server_id' => $result->serverId,
                'status' => $result->status->value,
                'errors' => $result->errors ?: null,
                'occurred_at' => $operation->occurredAt,
                'applied_at' => $result->status === SyncOperationStatus::Applied ? now() : null,
            ]);

            $batchStatuses[$operation->id] = $result->status->value;
            $results[] = $record->toResultArray();
        }

        return $results;
    }

    private function applyOperation(User $user, SyncOperation $operation, array $batchStatuses): SyncResult
    {
        $unmet = $this->unmetDependencies($user, $operation, $batchStatuses);

        if ($unmet !== []) {
            return SyncResult::failed([
                'depends_on' => ['Depends on operations that have not been applied: '.implode(', ', $unmet).'.'],
            ]);
        }

        $handler = $this->registry->handlerFor($operation->resource);

        if ($handler === null) {
            return SyncResult::failed([
                'resource' => ["Unknown sync resource [{$operation->resource}]."],
            ]);
        }

        try {
            return DB::transaction(fn () => $handler->apply($operation, $user));
        } catch (SyncConflictException $e) {
            return SyncResult::conflict($e->errors);
        } catch (ValidationException $e) {
            return SyncResult::failed($e->errors());
        } catch (Throwable $e) {
            report($e);

            return SyncResult::failed(['server' => ['The operation could not be processed.']]);
        }
    }

    /** @return list<string> dependency op ids not applied yet */
    private function unmetDependencies(User $user, SyncOperation $operation, array $batchStatuses): array
    {
        if ($operation->dependsOn === []) {
            return [];
        }

        $applied = SyncOperationStatus::Applied->value;

        $unresolved = collect($operation->dependsOn)
            ->reject(fn (string $depId) => ($batchStatuses[$depId] ?? null) === $applied);

        if ($unresolved->isEmpty()) {
            return [];
        }

        $storedApplied = SyncOperationRecord::whereIn('op_id', $unresolved)
            ->where('user_id', $user->getKey())
            ->where('status', $applied)
            ->pluck('op_id');

        return $unresolved->diff($storedApplied)->values()->all();
    }
}
