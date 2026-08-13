<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_reports_ok_when_all_dependencies_are_reachable(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'checks' => ['database' => 'ok', 'cache' => 'ok', 'queue' => 'ok'],
            ]);
    }

    public function test_reports_degraded_with_503_when_the_database_is_unreachable(): void
    {
        DB::shouldReceive('connection->getPdo')->andThrow(new RuntimeException('connection refused'));

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJson([
                'status' => 'degraded',
                'checks' => ['database' => 'fail'],
            ]);
    }

    public function test_reports_degraded_with_503_when_the_cache_is_unreachable(): void
    {
        Cache::shouldReceive('put')->andThrow(new RuntimeException('connection refused'));

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJson([
                'status' => 'degraded',
                'checks' => ['cache' => 'fail'],
            ]);
    }

    public function test_does_not_disclose_version_or_config_details(): void
    {
        $response = $this->getJson('/health');

        $response->assertJsonMissingPath('version')->assertJsonMissingPath('env');
    }
}
