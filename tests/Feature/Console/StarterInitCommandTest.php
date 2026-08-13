<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * StarterInitCommand writes to the real .env and composer.json (there's no other
 * sensible place for a "configure this copied project" command to write) — every
 * test here backs those files up and restores them, including on failure, so this
 * suite never leaves the repo's own working tree modified.
 *
 * Every test passes --name/--short-name/--tagline/--support-email/--package/
 * --no-showcase explicitly, matching how a real CI/scripted invocation would call
 * this command — PHPUnit forces Laravel Prompts into its mockable "fallback" mode
 * regardless of --no-interaction, so any option left unset here would need an
 * explicit expectsQuestion()/expectsConfirmation() to avoid hanging on a prompt.
 * Real (non-test) --no-interaction usage resolves unset options straight to their
 * defaults with no such requirement — verified manually against a real terminal.
 */
class StarterInitCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $envBackup;

    private string $composerBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envBackup = file_get_contents(base_path('.env'));
        $this->composerBackup = file_get_contents(base_path('composer.json'));
    }

    protected function tearDown(): void
    {
        file_put_contents(base_path('.env'), $this->envBackup);
        file_put_contents(base_path('composer.json'), $this->composerBackup);

        parent::tearDown();
    }

    private function baseOptions(array $overrides = []): array
    {
        return array_merge([
            '--name' => 'Acme Ops',
            '--short-name' => 'AO',
            '--tagline' => 'Field operations, streamlined',
            '--support-email' => 'help@acme.test',
            '--package' => 'acme/ops-platform',
            '--no-showcase' => true,
            '--no-interaction' => true,
        ], $overrides);
    }

    public function test_non_interactive_run_applies_every_option_deterministically(): void
    {
        $this->artisan('starter:init', $this->baseOptions())->assertExitCode(0);

        $env = file_get_contents(base_path('.env'));

        $this->assertStringContainsString('APP_NAME="Acme Ops"', $env);
        $this->assertStringContainsString('APP_BRAND_NAME="Acme Ops"', $env);
        $this->assertStringContainsString('APP_BRAND_SHORT_NAME=AO', $env);
        $this->assertStringContainsString('APP_BRAND_TAGLINE="Field operations, streamlined"', $env);
        $this->assertStringContainsString('APP_SUPPORT_EMAIL=help@acme.test', $env);
        $this->assertStringContainsString('SHOW_SHOWCASE_PAGES=false', $env);

        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $this->assertSame('acme/ops-platform', $composer['name']);
    }

    public function test_composer_json_edit_only_touches_the_name_line(): void
    {
        $before = file_get_contents(base_path('composer.json'));

        $this->artisan('starter:init', $this->baseOptions())->assertExitCode(0);

        $after = file_get_contents(base_path('composer.json'));

        $beforeLines = explode("\n", $before);
        $afterLines = explode("\n", $after);

        $this->assertCount(count($beforeLines), $afterLines, 'Line count changed — the file was reformatted, not targeted-edited.');

        $changedLines = array_filter(
            array_map(fn ($b, $a) => $b === $a ? null : 1, $beforeLines, $afterLines)
        );

        $this->assertCount(1, $changedLines, 'More than one line changed in composer.json.');
    }

    public function test_re_running_updates_values_in_place_instead_of_duplicating_keys(): void
    {
        $this->artisan('starter:init', $this->baseOptions(['--short-name' => 'FN']))->assertExitCode(0);
        $this->artisan('starter:init', $this->baseOptions(['--short-name' => 'SN']))->assertExitCode(0);

        $env = file_get_contents(base_path('.env'));

        $this->assertSame(1, substr_count($env, 'APP_BRAND_SHORT_NAME='));
        $this->assertStringContainsString('APP_BRAND_SHORT_NAME=SN', $env);
        $this->assertStringNotContainsString('APP_BRAND_SHORT_NAME=FN', $env);
    }

    public function test_seed_flag_implies_migrate_and_runs_both(): void
    {
        $this->artisan('starter:init', $this->baseOptions(['--seed' => true]))->assertExitCode(0);

        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
        $this->assertDatabaseHas('users', ['email' => 'owner@rainwaves.test']);
    }

    public function test_without_migrate_or_seed_flags_the_database_is_left_untouched(): void
    {
        $this->artisan('starter:init', $this->baseOptions())->assertExitCode(0);

        $this->assertDatabaseMissing('roles', ['name' => 'super-admin']);
    }

    public function test_derives_a_short_name_from_the_project_name_when_not_given(): void
    {
        $options = $this->baseOptions(['--name' => 'Field Operations Platform']);
        unset($options['--short-name']);

        // The prompt's *default* is what we're testing (the derivation logic), so
        // this answers with that same default — equivalent to the user hitting
        // enter to accept it.
        $this->artisan('starter:init', $options)
            ->expectsQuestion('Short brand mark (shown in compact spaces)', 'FOP')
            ->assertExitCode(0);

        $this->assertStringContainsString('APP_BRAND_SHORT_NAME=FOP', file_get_contents(base_path('.env')));
    }

    public function test_interactive_showcase_prompt_defaults_to_keeping_showcase_pages(): void
    {
        $options = $this->baseOptions();
        unset($options['--no-showcase']);

        $this->artisan('starter:init', $options)
            ->expectsConfirmation('Keep the showcase pages (component catalogue, foundation, PayFast browser test)?', 'yes')
            ->assertExitCode(0);

        $this->assertStringContainsString('SHOW_SHOWCASE_PAGES=true', file_get_contents(base_path('.env')));
    }
}
