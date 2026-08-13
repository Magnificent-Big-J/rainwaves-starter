<?php

namespace App\Modules;

/**
 * RS-301: the declarative metadata a ModuleRegistry needs to validate — dependencies,
 * conflicts, and the permissions a module owns. Routes and migrations are
 * deliberately *not* here: each module's own ServiceProvider registers those directly
 * via loadRoutesFrom()/loadMigrationsFrom(), the idiomatic Laravel mechanism, rather
 * than this interface duplicating what ServiceProvider already does well.
 */
interface ModuleManifest
{
    public function name(): string;

    /** @return list<string> Permission strings this module owns (documentation/introspection, not enforcement). */
    public function permissions(): array;

    /** @return list<string> Other module names this module requires to be enabled. */
    public function dependencies(): array;

    /** @return list<string> Other module names that cannot be enabled at the same time as this one. */
    public function conflicts(): array;
}
