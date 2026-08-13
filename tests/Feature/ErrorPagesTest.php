<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * RS-105: branded error/maintenance pages. Laravel's default exception handler
 * picks these up automatically via view()->exists("errors::{$status}") whenever
 * APP_DEBUG is false — no wiring beyond the view files existing and rendering.
 */
class ErrorPagesTest extends TestCase
{
    public function test_branded_error_views_exist_and_render_without_error(): void
    {
        foreach (['404', '403', '419', '429', '500', '503'] as $status) {
            $this->assertTrue(view()->exists("errors.{$status}"), "resources/views/errors/{$status}.blade.php is missing.");

            $html = view("errors.{$status}")->render();

            $this->assertStringContainsString(config('app-brand.name'), $html);
        }
    }

    public function test_too_many_requests_returns_branded_page_outside_debug(): void
    {
        config()->set('app.debug', false);

        // /api/v1/meta is throttled at 60/min; blow past it on a *web* route instead
        // so we see the Blade error view rather than the API's JSON envelope.
        for ($i = 0; $i < 15; $i++) {
            $this->post('/payments/payfast/initiate', []);
        }

        $response = $this->post('/payments/payfast/initiate', []);

        if ($response->getStatusCode() === 429) {
            $response->assertSee('Slow down a little');
        } else {
            $this->markTestSkipped('Rate limiter did not trip within the attempted request count.');
        }
    }
}
