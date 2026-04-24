<template>
    <div class="dashboard">

        <div class="dashboard__header">
            <div>
                <p class="label">{{ greeting }}</p>
                <h1 class="dashboard__name">{{ firstName }}<span class="dashboard__dot">.</span></h1>
            </div>
            <div class="dashboard__meta">
                <span class="dashboard__date">{{ today }}</span>
            </div>
        </div>

        <div class="stat-row">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="stat-card"
            >
                <div class="stat-card__icon" :style="{ background: stat.bg }">
                    <v-icon :color="stat.iconColor" size="18">{{ stat.icon }}</v-icon>
                </div>
                <div>
                    <div class="stat-card__value">{{ stat.value }}</div>
                    <div class="stat-card__label">{{ stat.label }}</div>
                </div>
            </div>
        </div>

        <div class="dashboard__body">
            <section class="modules-section">
                <h2 class="section-title">Platform modules</h2>
                <p class="section-sub">Every module is live and wired. Start with what you need.</p>

                <div class="module-grid">
                    <RouterLink
                        v-for="mod in modules"
                        :key="mod.to"
                        :to="mod.to"
                        class="module-card"
                    >
                        <div class="module-card__top">
                            <span class="module-card__icon">
                                <v-icon size="20" :color="mod.color">{{ mod.icon }}</v-icon>
                            </span>
                            <span class="module-card__arrow">
                                <v-icon size="14" color="var(--rw-dim)">mdi-arrow-right</v-icon>
                            </span>
                        </div>
                        <h3 class="module-card__title">{{ mod.title }}</h3>
                        <p class="module-card__text">{{ mod.text }}</p>
                    </RouterLink>
                </div>
            </section>

            <aside class="dashboard__aside">
                <section class="info-card">
                    <h3 class="info-card__title">Signed in as</h3>
                    <div class="identity">
                        <span class="identity__avatar">{{ userInitials }}</span>
                        <div class="identity__info">
                            <span class="identity__name">{{ session.user?.name }}</span>
                            <span class="identity__email">{{ session.user?.email }}</span>
                        </div>
                    </div>
                    <div class="role-list">
                        <span
                            v-for="role in session.user?.roles"
                            :key="role"
                            class="role-badge"
                        >
                            {{ role }}
                        </span>
                        <span v-if="!session.user?.roles?.length" class="role-none">No roles assigned</span>
                    </div>
                </section>

                <section class="info-card">
                    <h3 class="info-card__title">Stack</h3>
                    <ul class="stack-list">
                        <li v-for="item in stackItems" :key="item.name" class="stack-list__item">
                            <span class="stack-list__name">{{ item.name }}</span>
                            <span class="stack-list__ver">{{ item.ver }}</span>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>

    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Dashboard"
    }
}
</route>

<script setup>
import { computed } from 'vue';

import { useSessionStore } from '../stores/session';

const session = useSessionStore();

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 17) return 'Good afternoon';
    return 'Good evening';
});

const firstName = computed(() =>
    session.user?.name?.split(' ')[0] || 'there'
);

const userInitials = computed(() =>
    (session.user?.name || 'RW')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || '')
        .join('')
);

const today = computed(() =>
    new Date().toLocaleDateString('en-ZA', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
);

const stats = [
    { label: 'Active roles',       value: '4',    icon: 'mdi-shield-account-outline', bg: 'rgba(0,106,74,0.08)',  iconColor: 'var(--rw-600)' },
    { label: 'Permissions seeded', value: '21',   icon: 'mdi-key-outline',             bg: 'rgba(180,83,9,0.08)',  iconColor: 'var(--rw-amber)' },
    { label: 'Queue backend',      value: 'Redis', icon: 'mdi-database-outline',        bg: 'rgba(3,105,161,0.08)', iconColor: '#0369a1' },
    { label: 'Storage backend',    value: 'MinIO', icon: 'mdi-cloud-outline',           bg: 'rgba(101,16,147,0.08)', iconColor: '#6510a3' },
];

const modules = [
    {
        to: '/auth/profile',
        title: 'Profile & 2FA',
        text: 'Update your name, email, avatar, and manage two-factor authentication.',
        icon: 'mdi-account-circle-outline',
        color: 'var(--rw-600)',
    },
    {
        to: '/admin/users',
        title: 'User management',
        text: 'Create and manage users, assign roles and granular permissions.',
        icon: 'mdi-account-group-outline',
        color: 'var(--rw-amber)',
    },
    {
        to: '/about',
        title: 'About this starter',
        text: 'Full breakdown of every module, dependency, and design decision.',
        icon: 'mdi-layers-outline',
        color: '#0369a1',
    },
];

const stackItems = [
    { name: 'Laravel',         ver: '13' },
    { name: 'lara-auth-suite', ver: '2' },
    { name: 'payfast-payment', ver: '1' },
    { name: 'Vue + Vuetify',   ver: '3' },
    { name: 'Pinia',           ver: '2' },
    { name: 'Horizon',         ver: '5' },
];
</script>

<style scoped>
.dashboard {
    padding: 2.25rem 2rem 4rem;
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 2rem;
}

/* ── Header ────────────────────────────────────────── */
.dashboard__header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
}

.label {
    margin: 0 0 0.2rem;
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--rw-muted);
}

.dashboard__name {
    margin: 0;
    font-size: clamp(2rem, 4vw, 3.25rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--rw-ink);
    line-height: 1;
}

.dashboard__dot {
    color: var(--rw-600);
}

.dashboard__date {
    font-size: 0.8rem;
    color: var(--rw-dim);
    font-weight: 500;
}

/* ── Stats ─────────────────────────────────────────── */
.stat-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.875rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 1rem 1.25rem;
    background: var(--rw-surface);
    border-radius: 1rem;
    border: 1px solid var(--rw-border);
    box-shadow: var(--rw-shadow-xs);
}

.stat-card__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.625rem;
    flex-shrink: 0;
}

.stat-card__value {
    font-size: 1.2rem;
    font-weight: 800;
    color: var(--rw-ink);
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.stat-card__label {
    font-size: 0.75rem;
    color: var(--rw-muted);
    font-weight: 500;
    margin-top: 0.1rem;
}

/* ── Body ──────────────────────────────────────────── */
.dashboard__body {
    display: grid;
    gap: 1.5rem;
    align-items: start;
}

/* Modules ──────────────────────────────────────────── */
.section-title {
    margin: 0 0 0.3rem;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--rw-ink);
}

.section-sub {
    margin: 0 0 1.25rem;
    font-size: 0.85rem;
    color: var(--rw-muted);
}

.module-grid {
    display: grid;
    gap: 0.875rem;
}

.module-card {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 1.1rem 1.25rem;
    background: var(--rw-surface);
    border-radius: 1rem;
    border: 1px solid var(--rw-border);
    text-decoration: none;
    color: inherit;
    box-shadow: var(--rw-shadow-xs);
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
}

.module-card:hover {
    border-color: var(--rw-100);
    box-shadow: var(--rw-shadow);
    transform: translateY(-1px);
}

.module-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.module-card__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: var(--rw-surface-2);
}

.module-card__title {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--rw-ink);
}

.module-card__text {
    margin: 0;
    font-size: 0.8rem;
    color: var(--rw-muted);
    line-height: 1.5;
}

/* Aside ────────────────────────────────────────────── */
.info-card {
    padding: 1.1rem 1.25rem;
    background: var(--rw-surface);
    border: 1px solid var(--rw-border);
    border-radius: 1rem;
    box-shadow: var(--rw-shadow-xs);
    margin-bottom: 0.875rem;
}

.info-card__title {
    margin: 0 0 0.875rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--rw-dim);
}

.identity {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.875rem;
}

.identity__avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rw-700), var(--rw-500));
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
}

.identity__info {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
    min-width: 0;
}

.identity__name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--rw-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.identity__email {
    font-size: 0.775rem;
    color: var(--rw-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.role-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.role-badge {
    display: inline-flex;
    padding: 0.2rem 0.625rem;
    background: var(--rw-50);
    color: var(--rw-700);
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    border: 1px solid var(--rw-100);
}

.role-none {
    font-size: 0.78rem;
    color: var(--rw-dim);
}

.stack-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 0.5rem;
}

.stack-list__item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8rem;
}

.stack-list__name {
    color: var(--rw-ink-2);
    font-weight: 500;
}

.stack-list__ver {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--rw-muted);
    background: var(--rw-surface-2);
    padding: 0.1rem 0.5rem;
    border-radius: 999px;
    border: 1px solid var(--rw-border);
}

/* ── Responsive ────────────────────────────────────── */
@media (min-width: 640px) {
    .stat-row {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (min-width: 860px) {
    .dashboard__body {
        grid-template-columns: 1fr 260px;
    }

    .module-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
