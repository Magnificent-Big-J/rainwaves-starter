<?php

use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Rainwaves\LaraAuthSuite\Support\Enums\AuthMode;
use Rainwaves\LaraAuthSuite\Support\Enums\AuthFeature;
use Rainwaves\LaraAuthSuite\Support\Enums\TwoFactorChannel;

return [
    'route_prefix' => 'auth',
    'mode' => AuthMode::Both->value,
    'user_model' => User::class,
    'user_resource' => AuthUserResource::class,
    'debug_ping' => false, // enable GET /auth/ping for health checks (disable in production)
    'frontend' => [
        'password_reset_url' => env('AUTHX_FRONTEND_RESET_URL', '/auth/reset-password'),
    ],
    'features' => [
        AuthFeature::PasswordReset->value,
        AuthFeature::TwoFactor->value,
        AuthFeature::Tokens->value,
        AuthFeature::Devices->value,
    ],
    '2fa' => [
        'channels' => [TwoFactorChannel::Email->value, TwoFactorChannel::Totp->value], // allowed: email, totp
        'enforcement' => 'optional', // off | optional | required
        'remember_device_days' => 30,
        'otp' => [
            'length' => 6,
            'expiry_seconds' => 180,
            'throttle_per_minute' => 5,
        ],
        'totp_digits' => 6,
        'totp_period' => 30, // seconds per step (RFC 6238 default)
        'totp_window' => 1,  // steps either side of current accepted (1 = ±30s clock drift)
        'verification_ttl_seconds' => 600, // how long a verified 2FA state is trusted for token users
        'recovery_codes_count' => 8,
        'require_password_on_manage' => true,
    ],
    'tokens' => [
        'default_abilities' => ['*'],
        'expiry_minutes' => null,
    ],
    'throttle' => [
        'login' => 5,
        'two_factor' => 5,
        'reset' => 5,
        'decay_seconds' => 60,
    ],
    'registration' => [
        'enabled' => false,
        'issue_token_on_register' => false, // return PAT on success (token mode)
        'default_roles' => [], // e.g. ['client']
        'default_permissions' => [], // e.g. ['users.view']
        'allow_self_assign_roles' => false,
        'allow_self_assign_permissions' => false,
        'rules' => [ // host can override entirely in app/config/authx.php
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ],
        // Optional hook to supply rules dynamically (class must implement ProvidesValidationRules)
        'register_rules_provider' => null, // e.g. \App\Auth\RegisterRules::class
    ],

    'permissions' => [
        'enabled' => true, // if true, try assign roles/permissions via spatie/laravel-permission (if present)
        'fail_open_when_tables_missing' => false,
    ],
];
