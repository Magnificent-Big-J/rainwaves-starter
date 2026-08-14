<?php

namespace App\Modules\Governance\Contracts;

use App\Models\User;

interface LegalServiceInterface
{
    /**
     * @return list<array{document: string, version: int, accepted_version: int|null}>
     */
    public function statusFor(User $user): array;

    /**
     * @param  list<string>  $documents
     * @param  bool  $log  Pass false for a non-user-initiated acceptance (e.g. seeding
     *                     starter accounts with defaults) that shouldn't appear in the
     *                     audit trail as if the user actually clicked accept.
     */
    public function accept(User $user, array $documents, bool $log = true): void;

    /**
     * @param  iterable<int>  $userIds
     * @return array<int, bool> Keyed by user id — true if that user has accepted every
     *                          currently-configured document at its current version.
     */
    public function upToDateStatusFor(iterable $userIds): array;
}
