<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * RS-003 evidence: PayFast's local-only inspection/simulation tooling must not exist
 * in the route table at all outside local/testing (routes/payfast-local.php is only
 * ever loaded by bootstrap/app.php when app()->environment(['local', 'testing'])).
 * That registration decision happens once at boot, so it can't be exercised by
 * flipping the environment mid-process — this boots a real `production` process.
 */
class ProductionRouteHardeningTest extends TestCase
{
    public function test_dev_only_payfast_routes_are_absent_in_production(): void
    {
        $process = new Process(
            ['php', 'artisan', 'route:list', '--path=payments/payfast', '--json'],
            base_path(),
            array_merge($_SERVER, $_ENV, ['APP_ENV' => 'production'])
        );

        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        $routes = json_decode($process->getOutput(), true) ?? [];
        $uris = array_column($routes, 'uri');

        foreach (['payments/payfast/records', 'payments/payfast/simulate-itn', 'payments/payfast/subscriptions/action'] as $devOnlyUri) {
            $this->assertNotContains($devOnlyUri, $uris, "Dev-only PayFast route [{$devOnlyUri}] must not be registered in production.");
        }

        $this->assertContains('payments/payfast/itn', $uris, 'The ITN webhook must remain registered in production.');
        $this->assertContains('payments/payfast/initiate', $uris, 'Checkout initiation must remain registered in production.');
    }
}
