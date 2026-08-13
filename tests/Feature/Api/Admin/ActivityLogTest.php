<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_index_requires_activity_view_permission(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)
            ->getJson('/api/v1/activity-log')
            ->assertStatus(403);
    }

    public function test_index_lists_activity_with_causer_and_pagination(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        activity('security')->causedBy($owner)->log('Test event for audit log coverage');

        $response = $this->actingAs($owner)->getJson('/api/v1/activity-log');

        $response->assertOk()
            ->assertJsonPath('data.0.description', 'Test event for audit log coverage')
            ->assertJsonPath('data.0.causer.id', $owner->id)
            ->assertJsonStructure([
                'data' => [['id', 'log_name', 'description', 'event', 'causer', 'created_at']],
                'meta' => ['pagination', 'options' => ['log_names']],
            ]);
    }

    public function test_can_filter_by_log_name(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        activity('security')->causedBy($owner)->log('Security event');
        activity('other')->causedBy($owner)->log('Other event');

        $this->actingAs($owner)
            ->getJson('/api/v1/activity-log?log_name=security')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Security event');
    }
}
