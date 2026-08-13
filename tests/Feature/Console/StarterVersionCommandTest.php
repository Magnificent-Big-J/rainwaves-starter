<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class StarterVersionCommandTest extends TestCase
{
    public function test_reports_starter_version_and_key_facts(): void
    {
        // Each expectsOutputToContain() call consumes the first still-unconsumed line
        // that matches, so two assertions that would both match the *same* output line
        // ("Rainwaves Starter unreleased") must be combined into one — the second
        // assertion would otherwise find no other line to match and fail.
        $this->artisan('starter:version')
            ->expectsOutputToContain('Rainwaves Starter '.config('starter.version'))
            ->expectsOutputToContain(PHP_VERSION)
            ->expectsOutputToContain(app()->version())
            ->assertExitCode(0);
    }

    public function test_reports_the_installed_rainwaves_package_versions(): void
    {
        $this->artisan('starter:version')
            ->expectsOutputToContain('rainwaves/lara-auth-suite')
            ->expectsOutputToContain('rainwaves/payfast-payment')
            ->assertExitCode(0);
    }

    public function test_reports_the_configured_version_string(): void
    {
        config(['starter.version' => '9.9.9-test']);

        $this->artisan('starter:version')->expectsOutputToContain('9.9.9-test')->assertExitCode(0);
    }
}
