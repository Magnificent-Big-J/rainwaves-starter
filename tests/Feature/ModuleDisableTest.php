<?php

namespace Tests\Feature;

use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * RS-301 evidence: toggling MODULE_BILLING_ENABLED actually changes what's registered
 * — not just that the default (enabled) behaves like before. Both the provider
 * registration (bootstrap/providers.php) and migration path decisions happen once at
 * boot, so — same reasoning as ProductionRouteHardeningTest — this exercises real
 * subprocesses rather than flipping config mid-process.
 */
class ModuleDisableTest extends TestCase
{
    public function test_billing_routes_are_absent_when_the_module_is_disabled(): void
    {
        $uris = array_column($this->routesFor(['MODULE_BILLING_ENABLED' => 'false']), 'uri');

        foreach ($this->billingUris() as $uri) {
            $this->assertNotContains($uri, $uris, "Billing route [{$uri}] must not be registered when the module is disabled.");
        }
    }

    public function test_billing_routes_are_present_when_the_module_is_enabled(): void
    {
        $uris = array_column($this->routesFor(['MODULE_BILLING_ENABLED' => 'true']), 'uri');

        foreach ($this->billingUris() as $uri) {
            $this->assertContains($uri, $uris, "Billing route [{$uri}] must be registered when the module is enabled.");
        }
    }

    public function test_billing_routes_are_present_by_default(): void
    {
        // No MODULE_BILLING_ENABLED override at all — proves the default (unset env)
        // preserves pre-module-registry behaviour, not just the explicit "true" case.
        $uris = array_column($this->routesFor([]), 'uri');

        foreach ($this->billingUris() as $uri) {
            $this->assertContains($uri, $uris, "Billing route [{$uri}] must be registered by default.");
        }
    }

    public function test_migrate_fresh_does_not_create_billing_tables_when_the_module_is_disabled(): void
    {
        $tables = $this->tablesAfterMigrateFresh(['MODULE_BILLING_ENABLED' => 'false']);

        foreach (['subscriptions', 'payments', 'payment_events'] as $table) {
            $this->assertNotContains($table, $tables, "Table [{$table}] must not exist when the billing module is disabled.");
        }

        // Non-billing tables must still exist — proves this is a scoped, not a broken, migration run.
        $this->assertContains('users', $tables);
    }

    public function test_migrate_fresh_creates_billing_tables_when_the_module_is_enabled(): void
    {
        $tables = $this->tablesAfterMigrateFresh(['MODULE_BILLING_ENABLED' => 'true']);

        foreach (['subscriptions', 'payments', 'payment_events'] as $table) {
            $this->assertContains($table, $tables);
        }
    }

    /** @return list<string> */
    private function billingUris(): array
    {
        return [
            'payments/payfast/initiate',
            'payments/payfast/subscriptions/initiate',
            'payments/payfast/itn',
            'payments/payfast/return',
            'payments/payfast/cancel',
            'api/v1/billing',
            'api/v1/subscriptions',
            'api/v1/subscriptions/{subscription}/cancel',
        ];
    }

    /** @return list<array{uri: string}> */
    private function routesFor(array $env): array
    {
        $process = new Process(
            ['php', 'artisan', 'route:list', '--json'],
            base_path(),
            array_merge($_SERVER, $_ENV, $env)
        );

        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        return json_decode($process->getOutput(), true) ?? [];
    }

    /** @return list<string> */
    private function tablesAfterMigrateFresh(array $env): array
    {
        $dbPath = tempnam(sys_get_temp_dir(), 'module-disable-test').'.sqlite';

        $process = new Process(
            ['php', 'artisan', 'migrate:fresh', '--force'],
            base_path(),
            array_merge($_SERVER, $_ENV, $env, [
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $dbPath,
            ])
        );

        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        $pdo = new PDO('sqlite:'.$dbPath);
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);

        unlink($dbPath);

        return $tables;
    }
}
