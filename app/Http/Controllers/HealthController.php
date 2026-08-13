<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

/**
 * RS-502: Laravel's built-in `/up` (see bootstrap/app.php `health:`) is a liveness
 * probe only — it confirms the framework booted, nothing more. `/health` is a
 * readiness probe: it actually exercises the database, cache, and queue connections
 * this app depends on to serve real traffic, so a deploy/orchestrator can tell "booted
 * but can't reach its database" apart from "genuinely ready".
 *
 * Deliberately unauthenticated (infra probes this directly, not a logged-in user) and
 * deliberately minimal in what it discloses — per-check ok/fail only, no version or
 * config details, matching this project's fail-closed security posture elsewhere.
 */
class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json(
            [
                'status' => $healthy ? 'ok' : 'degraded',
                'checks' => array_map(fn (bool $ok) => $ok ? 'ok' : 'fail', $checks),
            ],
            $healthy ? 200 : 503
        );
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'health-check-probe';
            Cache::put($key, true, 5);

            return Cache::get($key) === true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkQueue(): bool
    {
        $connection = config('queue.default');

        // The sync driver runs jobs inline — no external dependency to check.
        if ($connection === 'sync') {
            return true;
        }

        try {
            // Resolves the underlying connection (pings Redis, etc.) without pushing
            // a real job onto it.
            Queue::connection($connection)->size('health-check-probe');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
