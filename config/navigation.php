<?php

return [

    // Roles that put a session on the admin surface (sidebar shell, /dashboard home).
    // Everyone else authenticated lands on the lighter customer surface. Drives
    // stores/session.js `isAdminSurface`/`homeRoute` via GET /api/v1/web-config —
    // no role names hardcoded in the frontend.
    'admin_roles' => ['super-admin', 'admin'],

    'home_routes' => [
        'admin' => '/dashboard',
        'customer' => '/customer/home',
        'guest' => '/',
    ],

    // Rendered in the authenticated sidebar (default.vue) for both surfaces unless
    // restricted by `surfaces`. `permission` (when set) additionally requires the
    // session user to carry that permission — see AuthUserResource `permissions`.
    'main' => [
        ['label' => 'Dashboard', 'to' => '/dashboard', 'icon' => 'mdi-view-dashboard-outline', 'surfaces' => ['admin']],
        ['label' => 'Home', 'to' => '/customer/home', 'icon' => 'mdi-home-outline', 'surfaces' => ['customer']],
        ['label' => 'Billing', 'to' => '/account/billing', 'icon' => 'mdi-credit-card-outline', 'surfaces' => ['admin', 'customer'], 'module' => 'billing'],
        ['label' => 'Team', 'to' => '/account/team', 'icon' => 'mdi-account-multiple-outline', 'surfaces' => ['customer'], 'module' => 'teams'],
        ['label' => 'Team Members', 'to' => '/account/team-members', 'icon' => 'mdi-account-group-outline', 'surfaces' => ['customer'], 'module' => 'teams'],
        ['label' => 'Notifications', 'to' => '/notifications', 'icon' => 'mdi-bell-outline', 'surfaces' => ['admin', 'customer']],
        ['label' => 'Profile', 'to' => '/profile', 'icon' => 'mdi-account-circle-outline', 'surfaces' => ['admin', 'customer']],
        ['label' => 'Sessions', 'to' => '/account/sessions', 'icon' => 'mdi-devices', 'surfaces' => ['admin', 'customer']],
    ],

    // Only ever rendered on the admin surface.
    'admin' => [
        ['label' => 'Users', 'to' => '/admin/users', 'icon' => 'mdi-account-group-outline', 'permission' => 'users.view'],
        // Admin "Teams" overview nav entry lands in Phase 3 alongside admin/teams.vue —
        // not added yet so this doesn't point at a page that doesn't exist.
        ['label' => 'Roles & Permissions', 'to' => '/admin/roles', 'icon' => 'mdi-shield-account-outline', 'permission' => 'roles.view'],
        ['label' => 'Audit Log', 'to' => '/admin/audit-log', 'icon' => 'mdi-history', 'permission' => 'activity.view'],
        ['label' => 'Settings', 'to' => '/admin/settings', 'icon' => 'mdi-cog-outline', 'permission' => 'settings.view'],
    ],

    // Only rendered when config('features.show_showcase_pages') is true.
    'showcase' => [
        ['label' => 'Components', 'to' => '/components', 'icon' => 'mdi-toy-brick-outline'],
        ['label' => 'Foundation', 'to' => '/foundation', 'icon' => 'mdi-view-grid-outline'],
        ['label' => 'PayFast Test', 'to' => '/payfast-browser-test', 'icon' => 'mdi-credit-card-check-outline', 'environments' => ['local', 'testing'], 'module' => 'billing'],
    ],

    // Rendered on the guest top bar (default.vue) and guest.vue's header.
    'guest' => [
        ['label' => 'About', 'to' => '/about'],
        ['label' => 'Support', 'to' => '/support'],
    ],

    // Footer-style links available on every surface regardless of auth state.
    'legal' => [
        ['label' => 'Privacy', 'to' => '/legal/privacy'],
        ['label' => 'Terms', 'to' => '/legal/terms'],
    ],

];
