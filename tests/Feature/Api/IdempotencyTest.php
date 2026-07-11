<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureIdempotency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        Sanctum::actingAs($this->user);
    }

    public function test_same_key_and_payload_replays_the_stored_response(): void
    {
        $key = (string) Str::uuid();
        $payload = ['uuid' => (string) Str::uuid(), 'platform' => 'android'];

        $first = $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', $payload);

        $first->assertStatus(201);
        $this->assertFalse($first->headers->has(EnsureIdempotency::REPLAY_HEADER));

        $replay = $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', $payload);

        $replay->assertStatus(201)
            ->assertHeader(EnsureIdempotency::REPLAY_HEADER, 'true');

        $this->assertSame($first->getContent(), $replay->getContent());
        $this->assertSame(1, $this->user->devices()->count());
    }

    public function test_same_key_with_different_payload_is_rejected_with_409(): void
    {
        $key = (string) Str::uuid();

        $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', ['uuid' => (string) Str::uuid(), 'platform' => 'android'])
            ->assertStatus(201);

        $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', ['uuid' => (string) Str::uuid(), 'platform' => 'ios'])
            ->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_validation_failures_are_replayed_too(): void
    {
        $key = (string) Str::uuid();
        $payload = ['uuid' => 'not-a-uuid', 'platform' => 'android'];

        $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', $payload)
            ->assertStatus(422);

        $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', $payload)
            ->assertStatus(422)
            ->assertHeader(EnsureIdempotency::REPLAY_HEADER, 'true');
    }

    public function test_requests_without_the_header_are_not_idempotent(): void
    {
        $payload = ['uuid' => (string) Str::uuid(), 'platform' => 'android'];

        $this->postJson('/api/v1/devices', $payload)->assertStatus(201);
        $this->postJson('/api/v1/devices', $payload)->assertStatus(200);
    }

    public function test_keys_are_scoped_per_user(): void
    {
        $key = (string) Str::uuid();
        $payload = ['uuid' => (string) Str::uuid(), 'platform' => 'android'];

        $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', $payload)
            ->assertStatus(201);

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::where('email', 'owner@rainwaves.test')->firstOrFail());

        // Same key, same payload, different user: executes fresh (201, not replay).
        $response = $this->withHeader(EnsureIdempotency::HEADER, $key)
            ->postJson('/api/v1/devices', $payload);

        $response->assertStatus(201);
        $this->assertFalse($response->headers->has(EnsureIdempotency::REPLAY_HEADER));
    }

    public function test_oversized_keys_are_rejected(): void
    {
        $this->withHeader(EnsureIdempotency::HEADER, str_repeat('k', 256))
            ->postJson('/api/v1/devices', ['uuid' => (string) Str::uuid(), 'platform' => 'android'])
            ->assertStatus(422);
    }
}
