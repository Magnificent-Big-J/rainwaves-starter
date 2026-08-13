<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Horizon;
use Throwable;

/**
 * RS-006: a single command that answers "is this deployment safe to serve traffic?"
 * Run plain for an informational report; add --production to turn every blocking
 * finding into a non-zero exit code, suitable for a deploy pipeline gate.
 */
class StarterDoctorCommand extends Command
{
    protected $signature = 'starter:doctor {--production : Fail (non-zero exit) if any blocking finding is present}';

    protected $description = 'Validate environment, security posture, and infrastructure readiness before serving traffic.';

    /** @var array<int, array{check: string, level: string, message: string}> */
    private array $findings = [];

    public function handle(): int
    {
        $production = (bool) $this->option('production');

        $this->checkAppKey();
        $this->checkAppEnv($production);
        $this->checkAppDebug($production);
        $this->checkPermissionsFailClosed($production);
        $this->checkDevRoutesAbsent($production);
        $this->checkDatabase();
        $this->checkPendingMigrations();
        $this->checkRedis();
        $this->checkQueueWorker($production);
        $this->checkStorageWritable();
        $this->checkMailConfigured($production);
        $this->checkPayFastConfig($production);
        $this->checkBuildManifest($production);

        $this->render();

        $blocking = array_filter($this->findings, fn (array $f) => $f['level'] === 'fail');

        if ($production && $blocking !== []) {
            $this->newLine();
            $this->error(sprintf('%d blocking finding(s) — not safe to run with --production.', count($blocking)));

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($blocking === [] ? 'No blocking findings.' : sprintf('%d finding(s) reported as warnings only (run with --production to enforce).', count($blocking)));

        return self::SUCCESS;
    }

    private function checkAppKey(): void
    {
        if (blank(config('app.key'))) {
            $this->report('app.key', 'fail', 'APP_KEY is not set — run `php artisan key:generate`.');

            return;
        }

        $this->report('app.key', 'pass', 'APP_KEY is set.');
    }

    private function checkAppEnv(bool $production): void
    {
        $env = config('app.env');

        if ($production && $env !== 'production') {
            $this->report('app.env', 'fail', "APP_ENV is '{$env}', expected 'production' when running --production checks.");

            return;
        }

        $this->report('app.env', 'pass', "APP_ENV is '{$env}'.");
    }

    private function checkAppDebug(bool $production): void
    {
        if (config('app.debug') === true) {
            $this->report('app.debug', $production ? 'fail' : 'warn', 'APP_DEBUG is true — stack traces and env values would leak to end users.');

            return;
        }

        $this->report('app.debug', 'pass', 'APP_DEBUG is false.');
    }

    private function checkPermissionsFailClosed(bool $production): void
    {
        $failOpen = (bool) config('authx.permissions.fail_open_when_tables_missing', false);

        if ($failOpen) {
            $this->report('authx.permissions', $production ? 'fail' : 'warn', 'authx.permissions.fail_open_when_tables_missing is true — a missing permission table would grant access instead of denying it.');

            return;
        }

        $this->report('authx.permissions', 'pass', 'Permission checks fail closed when tables are missing.');
    }

    private function checkDevRoutesAbsent(bool $production): void
    {
        $devOnlyUris = ['payments/payfast/records', 'payments/payfast/simulate-itn', 'payments/payfast/subscriptions/action'];

        $registered = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->intersect($devOnlyUris)
            ->values();

        if ($registered->isNotEmpty()) {
            $this->report('routes.dev-only', $production ? 'fail' : 'warn', 'Dev/inspection PayFast routes are registered: '.$registered->implode(', ').'.');

            return;
        }

        $this->report('routes.dev-only', 'pass', 'No dev/inspection PayFast routes are registered.');
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->report('database', 'pass', 'Database connection ('.DB::connection()->getName().') is reachable.');
        } catch (Throwable $e) {
            $this->report('database', 'fail', 'Database is unreachable: '.$e->getMessage());
        }
    }

    private function checkPendingMigrations(): void
    {
        try {
            /** @var Migrator $migrator */
            $migrator = $this->laravel['migrator'];

            if (! $migrator->repositoryExists()) {
                $this->report('migrations', 'fail', 'Migrations table does not exist — run `php artisan migrate`.');

                return;
            }

            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = array_diff(array_keys($files), $ran);

            if ($pending !== []) {
                $this->report('migrations', 'fail', count($pending).' pending migration(s) — run `php artisan migrate`.');

                return;
            }

            $this->report('migrations', 'pass', 'No pending migrations.');
        } catch (Throwable $e) {
            $this->report('migrations', 'fail', 'Could not determine migration status: '.$e->getMessage());
        }
    }

    private function checkRedis(): void
    {
        $usesRedis = in_array('redis', [config('queue.default'), config('cache.default'), config('session.driver')], true);

        if (! $usesRedis) {
            $this->report('redis', 'pass', 'Redis is not configured as queue/cache/session driver — skipped.');

            return;
        }

        try {
            Redis::connection()->ping();
            $this->report('redis', 'pass', 'Redis connection is reachable.');
        } catch (Throwable $e) {
            $this->report('redis', 'fail', 'Redis is configured but unreachable: '.$e->getMessage());
        }
    }

    private function checkQueueWorker(bool $production): void
    {
        if (config('queue.default') !== 'redis' || ! class_exists(Horizon::class)) {
            $this->report('horizon', 'pass', 'Horizon is not applicable for the current queue driver — skipped.');

            return;
        }

        try {
            $names = $this->laravel->make(MasterSupervisorRepository::class)->names();

            if ($names === []) {
                $this->report('horizon', 'warn', "No active Horizon master supervisor found — queued jobs (mail, notifications, sync) will not process until `php artisan horizon` is running. This is expected if the supervisor hasn't started yet.");

                return;
            }

            $this->report('horizon', 'pass', 'Horizon master supervisor is active ('.implode(', ', $names).').');
        } catch (Throwable $e) {
            $this->report('horizon', 'warn', 'Could not determine Horizon status: '.$e->getMessage());
        }
    }

    private function checkStorageWritable(): void
    {
        $disk = config('filesystems.default');

        try {
            $probe = '.starter-doctor-'.Str::random(8);
            Storage::disk($disk)->put($probe, 'ok');
            $ok = Storage::disk($disk)->get($probe) === 'ok';
            Storage::disk($disk)->delete($probe);

            $this->report('storage', $ok ? 'pass' : 'fail', $ok ? "Storage disk '{$disk}' is writable." : "Storage disk '{$disk}' did not round-trip a test file.");
        } catch (Throwable $e) {
            $this->report('storage', 'fail', "Storage disk '{$disk}' is not writable: ".$e->getMessage());
        }
    }

    private function checkMailConfigured(bool $production): void
    {
        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->report('mail', $production ? 'fail' : 'warn', "Mail driver is '{$mailer}' — no real email (verification, password reset, notifications) will be delivered.");

            return;
        }

        $this->report('mail', 'pass', "Mail driver is '{$mailer}'.");
    }

    private function checkPayFastConfig(bool $production): void
    {
        $sandboxDefaults = ['merchant_id' => '10004002', 'merchant_key' => 'q1cd2rdny4a53', 'pass_phrase' => 'payfast'];

        foreach ($sandboxDefaults as $key => $default) {
            if ((string) config("payfast.{$key}") === $default) {
                $this->report('payfast.credentials', $production ? 'fail' : 'warn', "payfast.{$key} is still the published sandbox default — replace it with real merchant credentials before accepting live payments.");

                return;
            }
        }

        if ($production && config('payfast.env') !== 'live') {
            $this->report('payfast.env', 'warn', "payfast.env is '".config('payfast.env')."', not 'live' — confirm this is intentional for this deployment.");

            return;
        }

        $this->report('payfast.credentials', 'pass', 'PayFast credentials do not match published sandbox defaults.');
    }

    private function checkBuildManifest(bool $production): void
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            $this->report('frontend.build', $production ? 'fail' : 'warn', 'public/build/manifest.json is missing — run `npm run build`.');

            return;
        }

        $this->report('frontend.build', 'pass', 'Frontend build manifest is present.');
    }

    private function report(string $check, string $level, string $message): void
    {
        $this->findings[] = ['check' => $check, 'level' => $level, 'message' => $message];
    }

    private function render(): void
    {
        $icons = ['pass' => '<fg=green>PASS</>', 'warn' => '<fg=yellow>WARN</>', 'fail' => '<fg=red>FAIL</>'];

        $this->table(
            ['', 'Check', 'Finding'],
            array_map(fn (array $f) => [$icons[$f['level']], $f['check'], $f['message']], $this->findings)
        );
    }
}
