<?php

use App\Enums\DevicePlatform;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;

return [

    // Oldest app version the API still supports; the app compares on boot
    // and forces an update below it.
    'min_app_version' => env('MOBILE_MIN_APP_VERSION', '1.0.0'),

    // Channels AppNotification subclasses deliver through. Add a push
    // channel (FCM/APNs) here later; subclasses need no changes.
    'notification_channels' => ['database'],

    // Feature flags surfaced to mobile clients via GET /api/v1/meta.
    'features' => [
        'sync' => true,
        'notifications' => true,
    ],

    // Enums exposed as {value, label} option sets in GET /api/v1/meta so
    // clients never hardcode value lists. Enum classes must expose label().
    'option_sets' => [
        'device_platforms' => DevicePlatform::class,
        'payment_statuses' => PaymentStatus::class,
        'subscription_statuses' => SubscriptionStatus::class,
    ],

];
