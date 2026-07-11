<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnvelopeTest extends TestCase
{
    use RefreshDatabase;

    private const ENVELOPE_KEYS = ['success', 'message', 'data', 'meta', 'errors'];

    public function test_success_responses_use_the_envelope_shape(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'owner@rainwaves.test')->firstOrFail());

        $this->getJson('/api/v1/ping')
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['status' => 'ok']])
            ->assertJsonStructure(self::ENVELOPE_KEYS);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.email', 'owner@rainwaves.test');

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.email', 'owner@rainwaves.test')
            ->assertJsonStructure(self::ENVELOPE_KEYS);
    }

    public function test_paginated_index_folds_pagination_into_meta(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'owner@rainwaves.test')->firstOrFail());

        $this->getJson('/api/v1/users?per_page=2')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination' => ['current_page', 'per_page', 'last_page', 'total'], 'options' => ['roles', 'permissions']],
            ]);
    }

    public function test_validation_failures_return_422_envelope_with_errors_map(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'owner@rainwaves.test')->firstOrFail());

        $this->patchJson('/api/v1/profile', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'data' => null])
            ->assertJsonStructure([...self::ENVELOPE_KEYS, 'errors' => ['email']]);
    }

    public function test_unauthenticated_requests_return_401_envelope(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated.', 'data' => null]);
    }

    public function test_forbidden_requests_return_403_envelope(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'customer@rainwaves.test')->firstOrFail());

        $this->getJson('/api/v1/users')
            ->assertStatus(403)
            ->assertJson(['success' => false, 'data' => null]);
    }

    public function test_unknown_api_routes_return_404_envelope(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Resource not found.', 'data' => null]);
    }
}
