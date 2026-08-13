<?php

namespace App\Modules;

use RuntimeException;

/**
 * RS-301: the runtime side of the module system. bootstrap/providers.php is the
 * actual on/off switch (it decides whether a module's ServiceProvider is even
 * instantiated, reading the same env var this class reads via config('modules.enabled')
 * — that file runs before the container exists, so it can't use this class directly).
 * This registry is what everything *else* — app code, the frontend bootstrap payload,
 * an enabled module's own ServiceProvider — asks "is X enabled?", and it's what
 * validates a module's declared dependencies/conflicts against what's actually enabled.
 */
class ModuleRegistry
{
    /** @var array<string, ModuleManifest> */
    private array $modules = [];

    public function __construct()
    {
        foreach (config('modules.modules', []) as $class) {
            $manifest = app($class);
            $this->modules[$manifest->name()] = $manifest;
        }

        $this->validateDependenciesAndConflicts();
    }

    public function isEnabled(string $name): bool
    {
        return (bool) config("modules.enabled.{$name}", false);
    }

    public function manifest(string $name): ?ModuleManifest
    {
        return $this->modules[$name] ?? null;
    }

    private function validateDependenciesAndConflicts(): void
    {
        foreach ($this->modules as $name => $manifest) {
            if (! $this->isEnabled($name)) {
                continue;
            }

            foreach ($manifest->dependencies() as $dependency) {
                if (! $this->isEnabled($dependency)) {
                    throw new RuntimeException("Module [{$name}] requires [{$dependency}], which is not enabled.");
                }
            }

            foreach ($manifest->conflicts() as $conflict) {
                if ($this->isEnabled($conflict)) {
                    throw new RuntimeException("Module [{$name}] conflicts with [{$conflict}], which is also enabled.");
                }
            }
        }
    }
}
