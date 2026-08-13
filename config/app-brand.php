<?php

return [

    // Full product name shown in the browser tab, auth shell, and guest header.
    'name' => env('APP_BRAND_NAME', env('APP_NAME', 'Rainwaves Starter')),

    // Short mark used in compact spaces (sidebar collapsed rail, avatar badges).
    'short_name' => env('APP_BRAND_SHORT_NAME', 'RW'),

    // One-line descriptor shown under the wordmark on guest/auth surfaces.
    'tagline' => env('APP_BRAND_TAGLINE', 'Production-ready Laravel + Vue starter'),

    'support_email' => env('APP_SUPPORT_EMAIL', 'support@example.com'),

    'legal' => [
        'terms_url' => env('APP_TERMS_URL', '/legal/terms'),
        'privacy_url' => env('APP_PRIVACY_URL', '/legal/privacy'),
    ],

    // Footer line on the guest/auth shells.
    'footer' => env('APP_BRAND_FOOTER', 'Rainwaves Starter — Laravel 13'),

];
