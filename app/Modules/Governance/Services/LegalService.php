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

    public function accept(User $user, array $documents): void
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

        if (function_exists('activity')) {
            activity('governance')
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties(['documents' => $documents])
                ->event('legal_accepted')
                ->log('Accepted legal documents');
        }
    }
}
