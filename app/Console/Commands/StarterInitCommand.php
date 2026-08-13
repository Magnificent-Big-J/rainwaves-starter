<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;

/**
 * RS-103: interactive (and CI-safe non-interactive) setup for a project freshly
 * copied from this starter. Every prompt below is a Laravel Prompts call with a
 * `default:` — when --no-interaction is passed (or STDIN isn't a TTY), Prompts
 * resolves straight to that default with zero I/O, so `--no-interaction` plus the
 * CLI options below is a fully deterministic, scriptable path (see RS-103's "CI/
 * script mode can initialise a project deterministically" requirement).
 *
 * Safe to re-run: it only ever rewrites the handful of .env keys listed below and
 * the composer.json "name" field, never anything else in either file.
 */
class StarterInitCommand extends Command
{
    protected $signature = 'starter:init
        {--name= : Product name (config/app-brand.php + APP_NAME)}
        {--short-name= : Short brand mark shown in compact spaces, e.g. initials}
        {--tagline= : One-line tagline shown on guest/auth screens}
        {--support-email= : Support contact email}
        {--package= : composer.json "name" field, e.g. acme/my-app}
        {--no-showcase : Disable the component-catalogue/foundation/PayFast-test showcase pages}
        {--migrate : Run migrations after configuring}
        {--seed : Seed roles/permissions + starter accounts (implies --migrate)}';

    protected $description = 'Configure a project freshly copied from the starter: brand, package name, and optionally the database.';

    public function handle(): int
    {
        $this->ensureEnvFileExists();

        $name = $this->option('name') ?: text(
            label: 'Project name',
            default: $this->currentEnvValue('APP_BRAND_NAME') ?? 'My App',
            required: true,
        );

        $shortName = $this->option('short-name') ?: text(
            label: 'Short brand mark (shown in compact spaces)',
            default: $this->deriveShortName($name),
            required: true,
        );

        $tagline = $this->option('tagline') ?? text(
            label: 'Tagline (optional, shown on guest/auth screens)',
            default: $this->currentEnvValue('APP_BRAND_TAGLINE') ?? '',
        );

        $supportEmail = $this->option('support-email') ?: text(
            label: 'Support email',
            default: $this->currentEnvValue('APP_SUPPORT_EMAIL') ?? 'support@example.com',
            required: true,
        );

        $package = $this->option('package') ?: text(
            label: 'Composer package name (vendor/package)',
            default: Str::slug($name) !== '' ? 'your-org/'.Str::slug($name) : 'your-org/my-app',
            validate: fn (string $value) => preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9]([_.-]?[a-z0-9]+)*$#', $value)
                ? null
                : 'Must look like vendor/package (lowercase, e.g. acme/my-app).',
        );

        $keepShowcase = $this->option('no-showcase')
            ? false
            : confirm(
                label: 'Keep the showcase pages (component catalogue, foundation, PayFast browser test)?',
                default: true,
                hint: 'You can toggle this later at any time via SHOW_SHOWCASE_PAGES — see CLAUDE.md.',
            );

        // Deliberately option-only, no confirm() prompt: this command's job is
        // configuration, and silently deciding to touch the database — even with a
        // prompted yes/no — is a bigger surprise than requiring an explicit
        // --migrate/--seed flag for anyone who wants that too.
        $shouldSeed = (bool) $this->option('seed');
        $shouldMigrate = $shouldSeed || (bool) $this->option('migrate');

        $this->applyEnv([
            'APP_NAME' => $name,
            'APP_BRAND_NAME' => $name,
            'APP_BRAND_SHORT_NAME' => $shortName,
            'APP_BRAND_TAGLINE' => $tagline,
            'APP_SUPPORT_EMAIL' => $supportEmail,
            'APP_BRAND_FOOTER' => "{$name} — Laravel ".explode('.', Application::VERSION)[0],
            'SHOW_SHOWCASE_PAGES' => $keepShowcase ? 'true' : 'false',
        ]);

        $this->applyComposerPackageName($package);

        if (blank($this->currentEnvValue('APP_KEY'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        if ($shouldMigrate) {
            $this->call('migrate', ['--force' => true]);
        }

        if ($shouldSeed) {
            $this->call('db:seed', ['--force' => true]);
        }

        note("Configured \"{$name}\" ({$shortName}) as {$package}.");
        $this->components->info('Next: npm install && npm run build.');
        $this->components->warn(
            'Structured config (brand name, tagline, nav) is done — the marketing prose on '
            .'resources/js/app/pages/index.vue and about.vue still says "Rainwaves Starter" '
            .'and needs a human rewrite. This command only ever touches config values, never copy.'
        );

        return self::SUCCESS;
    }

    private function ensureEnvFileExists(): void
    {
        if (! file_exists(base_path('.env')) && file_exists(base_path('.env.example'))) {
            copy(base_path('.env.example'), base_path('.env'));
        }
    }

    private function currentEnvValue(string $key): ?string
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return null;
        }

        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', file_get_contents($path), $matches)) {
            return null;
        }

        return trim($matches[1], " \t\n\r\0\x0B\"'") ?: null;
    }

    /** @param  array<string, string>  $values */
    private function applyEnv(array $values): void
    {
        $path = base_path('.env');
        $contents = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $escaped = str_contains($value, ' ') || str_contains($value, '#') ? '"'.str_replace('"', '\"', $value).'"' : $value;
            $line = "{$key}={$escaped}";
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            $contents = preg_match($pattern, $contents)
                ? preg_replace($pattern, $line, $contents)
                : rtrim($contents)."\n{$line}\n";
        }

        file_put_contents($path, $contents);
    }

    private function applyComposerPackageName(string $package): void
    {
        $path = base_path('composer.json');
        $contents = file_get_contents($path);

        // A targeted string replace of just the "name" value, not a decode/re-encode
        // of the whole file — json_encode() would silently reformat every other key
        // (e.g. collapsing/expanding arrays) and produce a diff full of unrelated
        // noise on top of the one line that actually changed.
        $updated = preg_replace('/"name":\s*"[^"]*"/', '"name": "'.$package.'"', $contents, limit: 1);

        if ($updated !== null && $updated !== $contents) {
            file_put_contents($path, $updated);
        }
    }

    private function deriveShortName(string $name): string
    {
        $initials = collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->map(fn (string $word) => Str::upper($word[0]))
            ->take(3)
            ->implode('');

        return $initials ?: 'APP';
    }
}
