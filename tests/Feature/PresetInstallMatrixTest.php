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
 * modules) so this scales automatically when a new module is added — the
 * combination count doubles per module, not something this file needs to know
 * about. Teams' real dependency on Billing (TeamsModule::dependencies()) means
 * one combination in this matrix — teams enabled, billing disabled — is
 * expected to fail fast at boot rather than install cleanly; that branch is
 * handled explicitly below rather than being a silent gap in the matrix.
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
        'MODULE_TEAMS_ENABLED',
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
        ]);

        // Teams depends on Billing (TeamsModule::dependencies()) — bootstrap/providers.php
        // throws at boot before any provider registers when that's violated. This is the
        // one combination in the matrix that's *expected* to fail, not install cleanly.
        $expectBootFailure = $moduleEnv['MODULE_TEAMS_ENABLED'] === 'true' && $moduleEnv['MODULE_BILLING_ENABLED'] === 'false';

        try {
            if ($expectBootFailure) {
                // Every artisan command boots the framework first, so config:clear itself
                // fails here too — no "clean" command exists to run beforehand.
                $this->runArtisan(['config:cache'], $env, $label, expectSuccess: false);

                return;
            }

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

            $expectTeamsTables = $moduleEnv['MODULE_TEAMS_ENABLED'] === 'true';
            $this->assertSame(
                $expectTeamsTables,
                in_array('team_memberships', $tables, true),
                "[{$label}] team_memberships table presence must match MODULE_TEAMS_ENABLED."
            );
        } finally {
            // Best-effort cache cleanup — not itself under test, and in the expected-
            // failure case these commands fail too (the same boot-time check applies to
            // every artisan invocation), so no assertion is made either way here.
            $this->runArtisan(['route:clear'], $env, $label, expectSuccess: null);
            $this->runArtisan(['config:clear'], $env, $label, expectSuccess: null);
            @unlink($dbPath);
        }
    }

    private function runArtisan(array $command, array $env, string $label, ?bool $expectSuccess = true): void
    {
        $process = new Process(['php', 'artisan', ...$command], base_path(), $env);
        $process->run();

        if ($expectSuccess === null) {
            return;
        }

        if ($expectSuccess === false) {
            $this->assertFalse(
                $process->isSuccessful(),
                "[{$label}] php artisan ".implode(' ', $command).' was expected to fail (teams enabled without billing) but succeeded.'
            );
            $this->assertStringContainsString(
                'teams',
                strtolower($process->getOutput().$process->getErrorOutput()),
                "[{$label}] failure output should mention the teams/billing dependency violation."
            );

            return;
        }

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
