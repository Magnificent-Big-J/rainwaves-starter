<?php

namespace App\Modules\Governance\Services;

use App\Models\User;
use App\Modules\Governance\Contracts\LegalServiceInterface;
use App\Modules\Governance\Models\LegalAcceptance;
use Illuminate\Support\Facades\DB;

class LegalService implements LegalServiceInterface
{
    public function statusFor(User $user): array
    {
        $versions = config('governance.legal_versions', []);

        $latestAccepted = LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->whereIn('document', array_keys($versions))
            ->orderByDesc('version')
            ->get()
            ->groupBy('document')
            ->map(fn ($rows) => $rows->max('version'));

        return collect($versions)
            ->map(fn (int $version, string $document) => [
                'document' => $document,
                'version' => $version,
                'accepted_version' => $latestAccepted->get($document),
            ])
            ->values()
            ->all();
    }

    public function accept(User $user, array $documents, bool $log = true): void
    {
        $versions = config('governance.legal_versions', []);

        DB::transaction(function () use ($user, $documents, $versions) {
            foreach ($documents as $document) {
                if (! array_key_exists($document, $versions)) {
                    continue;
                }

                LegalAcceptance::query()->create([
                    'user_id' => $user->id,
                    'document' => $document,
                    'version' => $versions[$document],
                    'accepted_at' => now(),
                ]);
            }
        });

        // Suppressed for the seed-time default acceptance (StarterAccountsSeeded) —
        // that's a bootstrapping convenience, not a real user action worth an audit
        // trail entry, and it was polluting activity-log tests' "most recent" ordering
        // assumption when it fired alongside their own log() calls in the same second.
        if ($log && function_exists('activity')) {
            activity('governance')
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['documents' => $documents])
                ->event('legal_accepted')
                ->log('Accepted legal documents');
        }
    }

    public function upToDateStatusFor(iterable $userIds): array
    {
        $versions = config('governance.legal_versions', []);
        $userIds = collect($userIds)->values();

        if ($userIds->isEmpty() || $versions === []) {
            return $userIds->mapWithKeys(fn (int $id) => [$id => true])->all();
        }

        $latestByUser = LegalAcceptance::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('document', array_keys($versions))
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->groupBy('document')->map(fn ($rows) => $rows->max('version')));

        return $userIds->mapWithKeys(function (int $id) use ($versions, $latestByUser) {
            $userVersions = $latestByUser->get($id, collect());
            $upToDate = collect($versions)->every(fn (int $version, string $document) => ($userVersions[$document] ?? null) === $version);

            return [$id => $upToDate];
        })->all();
    }
}
