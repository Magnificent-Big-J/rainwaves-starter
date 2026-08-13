<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StarterDoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_run_reports_findings_but_exits_successfully_even_with_warnings(): void
    {
        config()->set('app.debug', true);

        $this->artisan('starter:doctor')
            ->expectsOutputToContain('app.debug')
            ->assertExitCode(0);
    }

    public function test_production_flag_fails_the_command_when_app_debug_is_enabled(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', true);

        $this->artisan('starter:doctor --production')
            ->assertExitCode(1);
    }

    public function test_production_flag_fails_when_permissions_are_configured_fail_open(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        config()->set('authx.permissions.fail_open_when_tables_missing', true);

        $this->artisan('starter:doctor --production')
            ->assertExitCode(1);
    }

    public function test_production_flag_fails_when_payfast_still_uses_published_sandbox_credentials(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        config()->set('payfast.merchant_id', '10004002');

        $this->artisan('starter:doctor --production')
            ->assertExitCode(1);
    }

    public function test_production_flag_fails_when_frontend_build_manifest_is_missing(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        config()->set('payfast.merchant_id', 'real-live-merchant-id');
        config()->set('payfast.merchant_key', 'real-live-merchant-key');
        config()->set('payfast.pass_phrase', 'real-live-pass-phrase');

        $manifest = public_path('build/manifest.json');
        $renamed = $manifest.'.bak';
        $existed = is_file($manifest);

        if ($existed) {
            rename($manifest, $renamed);
        }

        try {
            $this->artisan('starter:doctor --production')
                ->expectsOutputToContain('frontend.build')
                ->assertExitCode(1);
        } finally {
            if ($existed) {
                rename($renamed, $manifest);
            }
        }
    }

    public function test_dev_only_payfast_routes_registered_fails_under_production_flag(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.debug', false);
        config()->set('payfast.merchant_id', 'real-live-merchant-id');
        config()->set('payfast.merchant_key', 'real-live-merchant-key');
        config()->set('payfast.pass_phrase', 'real-live-pass-phrase');

        // Testing env keeps routes/payfast-local.php registered (see routes/web.php),
        // so this proves the doctor command's own live route inspection — not just the
        // route registration guard — would catch it if that guard were ever weakened.
        $this->artisan('starter:doctor --production')
            ->expectsOutputToContain('routes.dev-only')
            ->assertExitCode(1);
    }
}
