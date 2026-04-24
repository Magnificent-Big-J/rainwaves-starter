<?php

namespace Tests\Feature;

use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StarterPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_ready_to_use_starter_accounts(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $ops = User::where('email', 'ops@rainwaves.test')->firstOrFail();
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->assertTrue($owner->hasRole('super-admin'));
        $this->assertTrue($ops->hasRole('admin'));
        $this->assertTrue($customer->hasRole('customer'));
        $this->assertDatabaseHas('two_factor_secrets', [
            'user_id' => $ops->id,
            'type' => 'email',
        ]);
    }

    public function test_auth_user_resource_exposes_roles_permissions_and_avatar_url(): void
    {
        $this->seed();

        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $payload = AuthUserResource::make($owner)->toArray(Request::create('/'));

        $this->assertSame('owner@rainwaves.test', $payload['email']);
        $this->assertContains('super-admin', $payload['roles']);
        $this->assertContains('payments.manage', $payload['permissions']);
        $this->assertNull($payload['avatar_url']);
    }
}
