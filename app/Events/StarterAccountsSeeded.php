<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired once at the end of StarterUsersSeeder — lets an optional module enrich the
 * seeded starter accounts (e.g. Governance pre-accepting legal documents so they're
 * not stuck behind their own onboarding gate) without the seeder needing to know that
 * module exists. Same decoupling shape as lara-auth-suite's UserRegistered event,
 * which AppServiceProvider's LogSecurityActivity listener reacts to the same way.
 */
class StarterAccountsSeeded
{
    use Dispatchable;

    /** @param  list<User>  $users */
    public function __construct(public readonly array $users) {}
}
