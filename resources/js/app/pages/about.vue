<template>
    <div class="about-page">
        <section class="about-hero">
            <div class="about-hero__copy">
                <p class="eyebrow">About this starter</p>
                <h1 class="about-hero__title">
                    Everything a secure production app needs on day one.
                </h1>
                <p class="about-hero__text">
                    Rainwaves Starter is an internal baseline for Laravel 13 + Vue apps.
                    It ships with auth, roles, media, and payments wired up so new projects
                    start at a higher floor instead of rebuilding the same plumbing every time.
                </p>
                <div class="about-hero__cta">
                    <v-btn color="primary" size="large" to="/auth/login">Sign in</v-btn>
                    <v-btn variant="outlined" size="large" to="/auth/register">Register</v-btn>
                </div>
            </div>
        </section>

        <section class="about-grid">
            <div
                v-for="feature in features"
                :key="feature.title"
                class="feature-card"
            >
                <div class="feature-card__icon">
                    <v-icon :color="feature.color" size="22">{{ feature.icon }}</v-icon>
                </div>
                <div>
                    <h3 class="feature-card__title">{{ feature.title }}</h3>
                    <p class="feature-card__text">{{ feature.text }}</p>
                    <ul v-if="feature.bullets" class="feature-card__bullets">
                        <li v-for="b in feature.bullets" :key="b">{{ b }}</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="stack-section">
            <p class="eyebrow">Tech stack</p>
            <h2 class="stack-section__title">Built on a predictable foundation.</h2>

            <div class="stack-grid">
                <div v-for="item in stack" :key="item.name" class="stack-item">
                    <span class="stack-item__name">{{ item.name }}</span>
                    <span class="stack-item__version">{{ item.version }}</span>
                    <span class="stack-item__role">{{ item.role }}</span>
                </div>
            </div>
        </section>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "About"
    }
}
</route>

<script setup>
const features = [
    {
        icon: 'mdi-shield-lock-outline',
        color: 'primary',
        title: 'Auth & two-factor security',
        text: 'Session-based authentication via Laravel Sanctum with full 2FA support out of the box.',
        bullets: [
            'Email and password login with CSRF protection',
            'TOTP authenticator app setup and verification',
            'Email OTP as an alternative second factor',
            'Recovery codes for account fallback',
            'Registration disabled by default',
        ],
    },
    {
        icon: 'mdi-account-group-outline',
        color: 'secondary',
        title: 'Roles & permissions',
        text: 'Granular access control that ships pre-seeded and is ready to enforce immediately.',
        bullets: [
            'super-admin, admin, staff, customer roles',
            'Namespaced permissions (users.view, payments.create, …)',
            'Route-level and policy-level enforcement',
            'Permission data embedded in the session payload',
        ],
    },
    {
        icon: 'mdi-image-outline',
        color: 'info',
        title: 'Media & avatar uploads',
        text: 'File management via Spatie Media Library, stored on MinIO (S3-compatible) by default.',
        bullets: [
            'Avatar upload with live preview on the profile page',
            'Automatic 320×320 conversion on upload',
            'Remove avatar support',
            'Storage abstracted — swap to any S3 provider via .env',
        ],
    },
    {
        icon: 'mdi-credit-card-outline',
        color: 'warning',
        title: 'PayFast payments',
        text: 'One-time and subscription payment flows ready to be wired to your domain features.',
        bullets: [
            'One-time payment initiation and form generation',
            'Subscription initiation with billing cycle config',
            'ITN webhook with signature + merchant validation',
            'Idempotent event log — safe to replay or retry',
            'Payment and subscription status backed by typed enums',
        ],
    },
    {
        icon: 'mdi-clipboard-text-clock-outline',
        color: 'success',
        title: 'Activity log',
        text: 'Audit trail via Spatie Activity Log. Admin user create and update actions are logged automatically.',
        bullets: [
            'Causedby (who did it) and performedOn (what was changed)',
            'Logged with custom event names and properties',
            'Queryable for audit interfaces or dashboards',
        ],
    },
    {
        icon: 'mdi-layers-outline',
        color: 'primary',
        title: 'Owned UI shell',
        text: 'No vendor template overrides. Every component is explicitly placed and straightforward to change.',
        bullets: [
            'Collapsible sidebar with rail mode',
            'Responsive auth layout with marketing panel',
            'AppDataTable, AppSectionCard, MediaUploader components',
            'Vuetify 3 with a custom Rainwaves theme',
        ],
    },
];

const stack = [
    { name: 'Laravel', version: '13', role: 'Backend framework' },
    { name: 'Sanctum', version: '4', role: 'API / session auth' },
    { name: 'lara-auth-suite', version: '2', role: 'Auth, 2FA, password reset' },
    { name: 'payfast-payment', version: '1', role: 'PayFast checkout' },
    { name: 'laravel-permission', version: '7', role: 'Roles & permissions' },
    { name: 'laravel-medialibrary', version: '11', role: 'File / media management' },
    { name: 'laravel-activitylog', version: '5', role: 'Audit trail' },
    { name: 'Horizon', version: '5', role: 'Queue monitoring' },
    { name: 'Vue', version: '3', role: 'Frontend framework' },
    { name: 'Vuetify', version: '3', role: 'UI component library' },
    { name: 'Pinia', version: '2', role: 'State management' },
    { name: 'Vite', version: '6', role: 'Build tool' },
];
</script>

<style scoped>
.about-page {
    padding: 2rem 1.25rem 5rem;
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 3.5rem;
}

.eyebrow {
    margin: 0 0 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: var(--starter-accent);
    font-size: 0.78rem;
    font-weight: 700;
}

.about-hero__copy {
    max-width: 720px;
}

.about-hero__title {
    margin: 0;
    font-size: clamp(2.2rem, 5vw, 4rem);
    line-height: 0.96;
}

.about-hero__text {
    margin: 1.25rem 0 0;
    color: var(--starter-muted);
    font-size: 1.05rem;
    max-width: 56ch;
    line-height: 1.65;
}

.about-hero__cta {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.75rem;
    flex-wrap: wrap;
}

.about-grid {
    display: grid;
    gap: 1.25rem;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
}

.feature-card {
    display: flex;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-radius: 1.25rem;
    background: rgba(255, 253, 248, 0.9);
    border: 1px solid rgba(17, 34, 51, 0.08);
    transition: box-shadow 0.2s;
}

.feature-card:hover {
    box-shadow: 0 6px 24px rgba(17, 34, 51, 0.07);
}

.feature-card__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    background: rgba(17, 34, 51, 0.05);
    flex-shrink: 0;
    margin-top: 0.1rem;
}

.feature-card__title {
    margin: 0 0 0.5rem;
    font-size: 1rem;
    font-weight: 700;
}

.feature-card__text {
    margin: 0 0 0.75rem;
    font-size: 0.875rem;
    color: var(--starter-muted);
    line-height: 1.55;
}

.feature-card__bullets {
    margin: 0;
    padding-left: 1.1rem;
    display: grid;
    gap: 0.3rem;
}

.feature-card__bullets li {
    font-size: 0.825rem;
    color: var(--starter-muted);
    line-height: 1.45;
}

.stack-section__title {
    margin: 0 0 1.5rem;
    font-size: clamp(1.5rem, 3vw, 2.25rem);
}

.stack-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

.stack-item {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 0.875rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
    background: rgba(255, 253, 248, 0.9);
}

.stack-item__name {
    font-weight: 700;
    font-size: 0.9rem;
}

.stack-item__version {
    font-size: 0.75rem;
    color: var(--starter-accent);
    font-weight: 600;
    background: rgba(0, 106, 82, 0.08);
    padding: 0.1rem 0.4rem;
    border-radius: 999px;
}

.stack-item__role {
    font-size: 0.8rem;
    color: var(--starter-muted);
    margin-left: auto;
}
</style>
