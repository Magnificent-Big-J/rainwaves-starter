<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Architecture-style regression guard: every mutating (POST/PUT/PATCH/DELETE) route
 * under api/v1 must require auth:sanctum, unless it's explicitly allow-listed here as
 * one of the few genuinely-public mutations (the login/2FA endpoints — the auth flow
 * itself can't require auth). Catches "forgot to add auth:sanctum to a new route" as
 * a failing test instead of a live vulnerability — sweeps the real route table rather
 * than re-asserting each individually maintained endpoint one at a time.
 */
class ApiRouteAuthorizationTest extends TestCase
{
    private const PUBLIC_MUTATING_ROUTES = [
        'api/v1/auth/login',
        'api/v1/auth/two-factor',
        // An invitee accepting a team invite has no account yet — see
        // TeamService::registerAndAcceptInvite(), which deliberately bypasses the
        // general registration gate rather than requiring it to be open.
        'api/v1/team-invites/{token}/register',
    ];

    public function test_every_mutating_api_v1_route_requires_authentication_unless_explicitly_public(): void
    {
        $checked = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            if (! array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }

            if (in_array($route->uri(), self::PUBLIC_MUTATING_ROUTES, true)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $requiresAuth = in_array('auth:sanctum', $middleware, true);

            $this->assertTrue(
                $requiresAuth,
                "Mutating route [{$route->uri()}] does not require auth:sanctum and is not in the public allow-list."
            );

            $checked[] = $route->uri();
        }

        // Guards against the sweep itself silently checking nothing (e.g. a route
        // registration change that makes every route stop matching api/v1/*).
        $this->assertNotEmpty($checked);
        $this->assertContains('api/v1/users', $checked);
    }

    public function test_the_public_allow_list_itself_is_not_authenticated(): void
    {
        foreach (self::PUBLIC_MUTATING_ROUTES as $uri) {
            $route = collect(Route::getRoutes())->first(fn ($r) => $r->uri() === $uri);

            $this->assertNotNull($route, "Expected route [{$uri}] to exist.");

            $middleware = $route->gatherMiddleware();
            $requiresAuth = in_array('auth:sanctum', $middleware, true);

            $this->assertFalse(
                $requiresAuth,
                "Route [{$uri}] is in the public allow-list but actually requires auth — remove it from the allow-list instead."
            );
        }
    }
}
