<?php

namespace Tests\Feature;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Gate 3 "preset install and test matrix". ModuleDisableTest proves each module's
 * own routes/migrations are gated correctly in isolation — one module flipped at a
 * time, everything else left at its default. It never proves the app genuinely
 * *installs and boots* under a real combination of toggles (e.g. billing AND
 * mobile both disabled together), and it never exercises config:cache/route:cache
 * — the way this app actually runs in production — only the always-uncached
 * route:list/migrate:fresh calls the other subprocess tests use.
 *
 * Module list is deliberately generic (env var names, not hardcoded to two
 * modules) so this scales automatically when SaaS/Governance are added later —
 * the combination count doubles per module, not something this file needs to
 * know about.
 *
 * config:cache/route:cache write real files into bootstrap/cache/ (unlike
 * route:list/migrate:fresh, which don't touch the working tree at all) — every
 * combination unconditionally clears them again in a finally block so a failed
 * assertion never leaves a stale cached config/route file behind for whatever
 * runs `php artisan` next, in CI or on a shared dev machine.
 */
class PresetInstallMatrixTest extends TestCase
{
    private const MODULE_ENV_VARS = [
        'MODULE_BILLING_ENABLED',
        'MODULE_MOBILE_ENABLED',
    ];

    /** @return array<string, array{0: array<string, string>}> */
    public static function moduleCombinations(): array
    {
        $vars = self::MODULE_ENV_VARS;
        $combinations = [];

        for ($mask = 0; $mask < 2 ** count($vars); $mask++) {
            $combination = [];

            foreach ($vars as $index => $envVar) {
                $combination[$envVar] = (($mask >> $index) & 1) === 1 ? 'true' : 'false';
            }

            $label = collect($combination)->map(fn ($v, $k) => "{$k}={$v}")->implode(' ');
            $combinations[$label] = [$combination];
        }

        return $combinations;
    }

    #[DataProvider('moduleCombinations')]
    public function test_the_app_installs_and_boots_cleanly_for_every_module_combination(array $moduleEnv): void
    {
        $label = collect($moduleEnv)->map(fn ($v, $k) => "{$k}={$v}")->implode(' ');
        $dbPath = tempnam(sys_get_temp_dir(), 'preset-matrix-test').'.sqlite';

        $env = array_merge($_SERVER, $_ENV, $moduleEnv, [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $dbPath,
            // This matrix is billing x mobile only — Teams depends on billing (see
            // TeamsModule::dependencies()), so left at its own default it would make
            // every billing=false combination here fail for an unrelated reason.
            // tests/Feature/ModuleDependencyMatrixTest.php covers the teams x billing
            // interaction (including the expected-failure case) on its own.
            'MODULE_TEAMS_ENABLED' => 'false',
        ]);

        try {
            $this->runArtisan(['config:clear'], $env, $label);
            $this->runArtisan(['config:cache'], $env, $label);
            $this->runArtisan(['route:cache'], $env, $label);
            $this->runArtisan(['migrate:fresh', '--seed', '--force'], $env, $label);

            $tables = $this->tablesIn($dbPath);
            $this->assertContains('users', $tables, "[{$label}] users table must exist regardless of module state.");

            $expectBillingTables = $moduleEnv['MODULE_BILLING_ENABLED'] === 'true';
            $this->assertSame(
                $expectBillingTables,
                in_array('payments', $tables, true),
                "[{$label}] payments table presence must match MODULE_BILLING_ENABLED."
            );

            $expectMobileTables = $moduleEnv['MODULE_MOBILE_ENABLED'] === 'true';
            $this->assertSame(
                $expectMobileTables,
                in_array('devices', $tables, true),
                "[{$label}] devices table presence must match MODULE_MOBILE_ENABLED."
            );
        } finally {
            $this->runArtisan(['route:clear'], $env, $label);
            $this->runArtisan(['config:clear'], $env, $label);
            @unlink($dbPath);
        }
    }

    private function runArtisan(array $command, array $env, string $label): void
    {
        $process = new Process(['php', 'artisan', ...$command], base_path(), $env);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            "[{$label}] php artisan ".implode(' ', $command)." failed:\n".$process->getErrorOutput()
        );
    }

    /** @return list<string> */
    private function tablesIn(string $dbPath): array
    {
        $pdo = new PDO('sqlite:'.$dbPath);

        return $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);
    }
}
