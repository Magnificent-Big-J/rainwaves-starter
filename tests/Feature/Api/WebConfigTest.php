<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class WebConfigTest extends TestCase
{
    public function test_web_config_is_public_and_returns_brand_navigation_and_features(): void
    {
        $this->getJson('/api/v1/web-config')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.brand.name', config('app-brand.name'))
            ->assertJsonPath('data.features.show_showcase_pages', config('features.show_showcase_pages'))
            ->assertJsonPath('data.navigation.admin_roles', config('navigation.admin_roles'))
            ->assertJsonStructure([
                'data' => [
                    'brand' => ['name', 'short_name', 'tagline', 'support_email', 'legal', 'footer'],
                    'features' => ['show_showcase_pages'],
                    'navigation' => ['admin_roles', 'home_routes', 'main', 'admin', 'showcase', 'guest', 'legal'],
                    'environment',
                ],
            ]);
    }
}
