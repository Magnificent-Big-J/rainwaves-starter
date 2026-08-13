<?php

namespace App\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * RS-501: a single command reporting exactly what's installed — the starter template
 * version, the running commit, and the versions of the two Rainwaves packages this
 * project is built on. Useful for support ("what does `starter:version` say?") when
 * someone reports an issue against a copied project.
 */
class StarterVersionCommand extends Command
{
    protected $signature = 'starter:version';

    protected $description = 'Report the installed starter template version and key package versions.';

    public function handle(): int
    {
        $this->line(sprintf('<info>Rainwaves Starter</info> %s', config('starter.version')));
        $this->line(config('starter.repository'));
        $this->newLine();

        $this->table(['', ''], [
            ['Git commit', $this->gitCommit() ?? 'unavailable (not a git checkout)'],
            ['Git branch', $this->gitBranch() ?? 'unavailable (not a git checkout)'],
            ['PHP', PHP_VERSION],
            ['Laravel', app()->version()],
            ['Node (pinned)', $this->pinnedNodeVersion() ?? 'unavailable (.node-version missing)'],
            ['rainwaves/lara-auth-suite', $this->packageVersion('rainwaves/lara-auth-suite')],
            ['rainwaves/payfast-payment', $this->packageVersion('rainwaves/payfast-payment')],
        ]);

        return self::SUCCESS;
    }

    private function packageVersion(string $package): string
    {
        if (! InstalledVersions::isInstalled($package)) {
            return 'not installed';
        }

        return InstalledVersions::getPrettyVersion($package) ?? 'unknown';
    }

    private function gitCommit(): ?string
    {
        return $this->git(['git', 'rev-parse', '--short', 'HEAD']);
    }

    private function gitBranch(): ?string
    {
        return $this->git(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
    }

    private function git(array $command): ?string
    {
        if (! File::isDirectory(base_path('.git'))) {
            return null;
        }

        $result = Process::path(base_path())->run($command);

        return $result->successful() ? trim($result->output()) : null;
    }

    private function pinnedNodeVersion(): ?string
    {
        $path = base_path('.node-version');

        return File::exists($path) ? trim(File::get($path)) : null;
    }
}
