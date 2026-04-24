<template>
    <div class="landing">

        <section class="landing-hero">
            <div class="landing-hero__kicker">
                <span class="landing-kicker-badge">
                    <v-icon size="13" color="rgba(0,200,140,0.9)">mdi-check-circle-outline</v-icon>
                    Production-ready starter
                </span>
            </div>

            <h1 class="landing-hero__title">
                The platform foundation<br>your app deserves.
            </h1>
            <p class="landing-hero__text">
                Rainwaves Starter ships with authentication, two-factor security,
                role-based access, payments, media, and a complete UI shell —
                so your team starts building features on day one.
            </p>
            <div class="landing-hero__cta">
                <RouterLink to="/auth/login" class="cta-primary">Sign in</RouterLink>
                <RouterLink to="/auth/register" class="cta-ghost">Request access</RouterLink>
            </div>
        </section>

        <section class="landing-features">
            <div
                v-for="feature in features"
                :key="feature.title"
                class="feature-card"
            >
                <div class="feature-card__icon">
                    <v-icon :color="feature.iconColor" size="20">{{ feature.icon }}</v-icon>
                </div>
                <h3 class="feature-card__title">{{ feature.title }}</h3>
                <p class="feature-card__text">{{ feature.text }}</p>
                <ul class="feature-card__bullets">
                    <li v-for="b in feature.bullets" :key="b">{{ b }}</li>
                </ul>
            </div>
        </section>

        <section class="landing-stack">
            <div class="landing-stack__head">
                <p class="eyebrow">Tech stack</p>
                <h2 class="landing-stack__title">Built on a predictable foundation.</h2>
            </div>
            <div class="stack-grid">
                <div v-for="item in stack" :key="item.name" class="stack-item">
                    <span class="stack-item__name">{{ item.name }}</span>
                    <span class="stack-item__ver">{{ item.version }}</span>
                    <span class="stack-item__role">{{ item.role }}</span>
                </div>
            </div>
        </section>

    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "guest",
        "title": "Home"
    }
}
</route>

<script setup>
const features = [
    {
        icon: 'mdi-shield-lock-outline',
        iconColor: '#4ade80',
        title: 'Auth & two-factor',
        text: 'Session-based auth via Laravel Sanctum with full 2FA support out of the box.',
        bullets: [
            'Email / password with CSRF protection',
            'TOTP authenticator app + email OTP',
            'Recovery codes for account fallback',
            'Registration disabled by default',
        ],
    },
    {
        icon: 'mdi-account-group-outline',
        iconColor: '#fbbf24',
        title: 'Roles & permissions',
        text: 'Granular access control that ships pre-seeded and is ready to enforce immediately.',
        bullets: [
            'super-admin, admin, staff, customer roles',
            'Namespaced permissions (users.view, …)',
            'Route-level and policy-level enforcement',
            'Permission data embedded in session payload',
        ],
    },
    {
        icon: 'mdi-image-outline',
        iconColor: '#60a5fa',
        title: 'Media & avatars',
        text: 'File management via Spatie Media Library, stored on MinIO (S3-compatible) by default.',
        bullets: [
            'Avatar upload with live preview',
            'Automatic 320×320 conversion on upload',
            'Remove avatar support',
            'Storage abstracted via .env',
        ],
    },
    {
        icon: 'mdi-credit-card-outline',
        iconColor: '#f97316',
        title: 'PayFast payments',
        text: 'One-time and subscription payment flows ready to wire to your domain features.',
        bullets: [
            'One-time and subscription checkout',
            'ITN webhook with signature validation',
            'Idempotent event log — safe to replay',
            'Typed enum statuses on every record',
        ],
    },
    {
        icon: 'mdi-clipboard-text-clock-outline',
        iconColor: '#34d399',
        title: 'Activity log',
        text: 'Audit trail via Spatie Activity Log. Admin actions are logged automatically.',
        bullets: [
            'CausedBy + performedOn on every event',
            'Custom event names and properties',
            'Queryable for audit dashboards',
        ],
    },
    {
        icon: 'mdi-layers-outline',
        iconColor: '#a78bfa',
        title: 'Owned UI shell',
        text: 'No vendor template overrides — every component is explicitly placed and easy to change.',
        bullets: [
            'Custom CSS sidebar, no v-navigation-drawer',
            'Pinia stores + ofetch API layer',
            'AppDataTable, MediaUploader, FormActions',
            'Vuetify 3 with a custom Rainwaves theme',
        ],
    },
];

const stack = [
    { name: 'Laravel',              version: '13', role: 'Backend framework' },
    { name: 'Sanctum',              version: '4',  role: 'Session / API auth' },
    { name: 'lara-auth-suite',      version: '2',  role: 'Auth, 2FA, password reset' },
    { name: 'payfast-payment',      version: '1',  role: 'PayFast checkout' },
    { name: 'laravel-permission',   version: '7',  role: 'Roles & permissions' },
    { name: 'laravel-medialibrary', version: '11', role: 'File / media management' },
    { name: 'laravel-activitylog',  version: '5',  role: 'Audit trail' },
    { name: 'Horizon',              version: '5',  role: 'Queue monitoring' },
    { name: 'Vue',                  version: '3',  role: 'Frontend framework' },
    { name: 'Vuetify',              version: '3',  role: 'UI component library' },
    { name: 'Pinia',                version: '2',  role: 'State management' },
    { name: 'Vite',                 version: '6',  role: 'Build tool' },
];
</script>

<style scoped>
.landing {
    max-width: 1100px;
    margin: 0 auto;
    padding: 4rem 2rem 6rem;
    display: grid;
    gap: 5rem;
}

/* ── Eyebrow ───────────────────────────────────────── */
.eyebrow {
    margin: 0 0 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: rgba(0, 200, 140, 0.8);
}

/* ── Hero ──────────────────────────────────────────── */
.landing-hero__kicker {
    margin-bottom: 1.5rem;
}

.landing-kicker-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.875rem;
    background: rgba(0, 135, 95, 0.15);
    border: 1px solid rgba(0, 135, 95, 0.3);
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
    color: rgba(0, 200, 140, 0.9);
    letter-spacing: 0.02em;
}

.landing-hero__title {
    margin: 0;
    font-size: clamp(2.6rem, 6vw, 4.5rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 0.97;
    color: #fff;
}

.landing-hero__text {
    margin: 1.5rem 0 0;
    font-size: 1.05rem;
    color: rgba(255, 255, 255, 0.5);
    max-width: 54ch;
    line-height: 1.7;
}

.landing-hero__cta {
    display: flex;
    gap: 0.75rem;
    margin-top: 2.25rem;
    flex-wrap: wrap;
}

.cta-primary {
    display: inline-flex;
    align-items: center;
    padding: 0.7rem 1.75rem;
    background: #00875f;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: 0.625rem;
    text-decoration: none;
    transition: background 0.14s;
}

.cta-primary:hover {
    background: #006a4a;
}

.cta-ghost {
    display: inline-flex;
    align-items: center;
    padding: 0.7rem 1.75rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: 0.625rem;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, 0.15);
    transition: border-color 0.14s, color 0.14s;
}

.cta-ghost:hover {
    border-color: rgba(255, 255, 255, 0.3);
    color: #fff;
}

/* ── Features ──────────────────────────────────────── */
.landing-features {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}

.feature-card {
    padding: 1.5rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    display: grid;
    gap: 0.6rem;
    transition: background 0.15s, border-color 0.15s;
}

.feature-card:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(0, 135, 95, 0.3);
}

.feature-card__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.625rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.feature-card__title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    letter-spacing: -0.01em;
}

.feature-card__text {
    margin: 0;
    font-size: 0.825rem;
    color: rgba(255, 255, 255, 0.45);
    line-height: 1.55;
}

.feature-card__bullets {
    margin: 0.25rem 0 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.25rem;
}

.feature-card__bullets li {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.35);
    line-height: 1.5;
}

/* ── Stack ─────────────────────────────────────────── */
.landing-stack__title {
    margin: 0;
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #fff;
}

.stack-grid {
    display: grid;
    gap: 0.625rem;
    margin-top: 1.75rem;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
}

.stack-item {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.07);
    transition: background 0.12s;
}

.stack-item:hover {
    background: rgba(255, 255, 255, 0.07);
}

.stack-item__name {
    font-size: 0.875rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.85);
}

.stack-item__ver {
    font-size: 0.72rem;
    font-weight: 600;
    color: rgba(0, 200, 140, 0.9);
    background: rgba(0, 135, 95, 0.18);
    border: 1px solid rgba(0, 135, 95, 0.3);
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    flex-shrink: 0;
}

.stack-item__role {
    font-size: 0.775rem;
    color: rgba(255, 255, 255, 0.3);
    margin-left: auto;
    text-align: right;
}

@media (max-width: 720px) {
    .landing {
        padding: 3rem 1rem 4rem;
        gap: 3.5rem;
    }

    .landing-hero__title br {
        display: none;
    }
}

@media (max-width: 480px) {
    .landing {
        padding-inline: 0.75rem;
    }

    .cta-primary,
    .cta-ghost {
        width: 100%;
        justify-content: center;
    }

    .landing-features,
    .stack-grid {
        grid-template-columns: 1fr;
    }
}
</style>
