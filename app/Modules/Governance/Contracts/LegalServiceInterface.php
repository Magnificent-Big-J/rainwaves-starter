<?php

namespace App\Modules\Governance\Contracts;

use App\Models\User;

interface LegalServiceInterface
{
    /**
     * @return list<array{document: string, version: int, accepted_version: int|null}>
     */
    public function statusFor(User $user): array;

    /** @param  list<string>  $documents */
    public function accept(User $user, array $documents): void;
}
