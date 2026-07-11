<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_is_public_and_returns_bootstrap_payload(): void
    {
        $this->getJson('/api/v1/meta')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.min_app_version', config('mobile.min_app_version'))
            ->assertJsonPath('data.features.sync', true)
            ->assertJsonPath('data.sync_resources.0', 'devices')
            ->assertJsonStructure([
                'data' => [
                    'min_app_version',
                    'features',
                    'sync_resources',
                    'option_sets' => ['device_platforms' => [['value', 'label']]],
                    'server_time',
                ],
            ]);
    }
}
