<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function seedSessionRow(User $user, string $id, int $minutesAgo = 0): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'PHPUnit/1.0',
            'payload' => base64_encode('irrelevant'),
            'last_activity' => now()->subMinutes($minutesAgo)->timestamp,
        ]);
    }

    public function test_index_lists_only_the_authenticated_users_sessions(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->seedSessionRow($owner, 'owner-session-a');
        $this->seedSessionRow($owner, 'owner-session-b', minutesAgo: 30);
        $this->seedSessionRow($customer, 'customer-session-a');

        $this->actingAs($owner)
            ->getJson('/api/v1/sessions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'owner-session-a')
            ->assertJsonMissing(['id' => 'customer-session-a']);
    }

    public function test_cannot_revoke_another_users_session(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->seedSessionRow($customer, 'customer-session-a');

        $this->actingAs($owner)
            ->deleteJson('/api/v1/sessions/customer-session-a')
            ->assertStatus(404);

        $this->assertDatabaseHas('sessions', ['id' => 'customer-session-a']);
    }

    public function test_can_revoke_own_other_session(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $this->seedSessionRow($owner, 'owner-session-old', minutesAgo: 60);

        $this->actingAs($owner)
            ->deleteJson('/api/v1/sessions/owner-session-old')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('sessions', ['id' => 'owner-session-old']);
    }

    public function test_revoking_a_nonexistent_session_id_404s(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $this->actingAs($owner)
            ->deleteJson('/api/v1/sessions/does-not-exist')
            ->assertStatus(404);
    }

    public function test_destroy_others_only_touches_the_authenticated_users_rows(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->seedSessionRow($owner, 'owner-session-a');
        $this->seedSessionRow($owner, 'owner-session-b');
        $this->seedSessionRow($customer, 'customer-session-a');

        $this->actingAs($owner)
            ->deleteJson('/api/v1/sessions/others')
            ->assertOk();

        $this->assertDatabaseHas('sessions', ['id' => 'customer-session-a']);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $owner->id)->whereIn('id', ['owner-session-a', 'owner-session-b'])->count());
    }
}
