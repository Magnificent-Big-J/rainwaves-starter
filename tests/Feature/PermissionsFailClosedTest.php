<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * RS-005 evidence: config/authx.php pins fail_open_when_tables_missing to false.
 * This proves what that default actually buys the app — if the Spatie permission
 * tables disappear (a botched migration, a bad rollback), the admin surface must
 * deny access rather than silently let everyone through.
 */
class PermissionsFailClosedTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_route_denies_access_without_the_required_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_admin_route_does_not_silently_allow_access_when_permission_tables_are_missing(): void
    {
        $this->assertFalse(
            config('authx.permissions.fail_open_when_tables_missing'),
            'This test only proves something if the fail-closed default is still in place.'
        );

        $user = User::factory()->create();

        // Simulate the tables having disappeared (bad rollback, botched migration)
        // after the app already booted — Spatie's permission checks now hit the DB
        // and find nothing there.
        Schema::disableForeignKeyConstraints();
        foreach (['model_has_permissions', 'model_has_roles', 'role_has_permissions', 'roles', 'permissions'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        $response = $this->actingAs($user)->getJson('/api/v1/users');

        // The security property under test: a missing permission table must never
        // resolve to "access granted". Whichever way Spatie's middleware surfaces the
        // missing-table condition (403 if it catches it, 500 if the query error
        // bubbles up), 200 is the one outcome that is never acceptable here.
        $this->assertNotEquals(200, $response->getStatusCode(), 'A missing permission table must never resolve to access being granted.');
    }
}
