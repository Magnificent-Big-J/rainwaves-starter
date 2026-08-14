<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['governance.legal_versions' => ['terms' => 1, 'privacy' => 1]]);
    }

    public function test_a_fresh_user_has_every_document_outstanding(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/legal/status')->assertOk();

        $documents = collect($response->json('data.documents'));

        $this->assertNull($documents->firstWhere('document', 'terms')['accepted_version']);
        $this->assertNull($documents->firstWhere('document', 'privacy')['accepted_version']);
    }

    public function test_accepting_a_document_clears_it_from_outstanding_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/legal/accept', ['documents' => ['terms']])
            ->assertOk();

        $response = $this->actingAs($user)->getJson('/api/v1/legal/status')->assertOk();
        $documents = collect($response->json('data.documents'));

        $this->assertSame(1, $documents->firstWhere('document', 'terms')['accepted_version']);
        $this->assertNull($documents->firstWhere('document', 'privacy')['accepted_version']);

        $this->assertDatabaseHas('legal_acceptances', [
            'user_id' => $user->id,
            'document' => 'terms',
            'version' => 1,
        ]);
    }

    public function test_a_version_bump_makes_a_previously_accepted_document_outstanding_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/legal/accept', ['documents' => ['terms']])->assertOk();

        config(['governance.legal_versions' => ['terms' => 2, 'privacy' => 1]]);

        $response = $this->actingAs($user)->getJson('/api/v1/legal/status')->assertOk();
        $documents = collect($response->json('data.documents'));

        $this->assertSame(1, $documents->firstWhere('document', 'terms')['accepted_version']);
        $this->assertSame(2, $documents->firstWhere('document', 'terms')['version']);
    }

    public function test_accept_rejects_an_unknown_document(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/legal/accept', ['documents' => ['not-a-real-document']])
            ->assertStatus(422);
    }

    public function test_status_and_accept_require_authentication(): void
    {
        $this->getJson('/api/v1/legal/status')->assertUnauthorized();
        $this->postJson('/api/v1/legal/accept', ['documents' => ['terms']])->assertUnauthorized();
    }
}
