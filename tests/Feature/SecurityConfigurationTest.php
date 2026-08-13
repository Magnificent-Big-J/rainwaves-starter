<?php

namespace Tests\Feature;

use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * A couple of cheap, generic architecture-style guards against classic
 * misconfiguration/leftover-debug-statement regressions — not exhaustive security
 * coverage (see PayFastItnHardeningTest, PermissionsFailClosedTest,
 * ApiRouteAuthorizationTest for the rest), just the two that are easy to check
 * automatically and easy to get wrong by accident.
 */
class SecurityConfigurationTest extends TestCase
{
    public function test_cors_does_not_combine_a_wildcard_origin_with_credentials(): void
    {
        // The classic real CORS misconfiguration: allowing every origin *and* sending
        // credentials (cookies/auth headers) defeats same-origin protection entirely.
        // Modern browsers reject this combination outright, but it's cheap to also
        // guard the config itself so a future change can't silently reintroduce it.
        $origins = config('cors.allowed_origins', []);
        $credentials = config('cors.supports_credentials', false);

        if ($credentials) {
            $this->assertNotContains('*', $origins, 'cors.supports_credentials is true, so cors.allowed_origins must not include "*".');
        } else {
            $this->assertFalse($credentials);
        }
    }

    public function test_no_debug_statements_left_in_application_code(): void
    {
        $finder = (new Finder)
            ->in(base_path('app'))
            ->name('*.php')
            ->files();

        $offenders = [];

        foreach ($finder as $file) {
            $contents = $file->getContents();

            foreach (['dd', 'dump', 'var_dump', 'ray', 'die', 'exit'] as $function) {
                // Negative lookbehind for an identifier character so this doesn't match
                // inside a longer name, e.g. in_array(/array_map( falsely matching "ray(".
                if (preg_match('/(?<![a-zA-Z0-9_])'.preg_quote($function, '/').'\s*\(/', $contents)) {
                    $offenders[] = $file->getRelativePathname().' contains '.$function.'()';
                }
            }
        }

        $this->assertEmpty($offenders, "Debug statement(s) left in app/:\n".implode("\n", $offenders));
    }
}
