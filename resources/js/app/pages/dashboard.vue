<template>
    <div class="dashboard">
        <AppPageHeader
            :eyebrow="greeting"
            :title="`${firstName}.`"
            subtitle="The starter dashboard now demonstrates reusable billing, activity, and security-ready surfaces as first-class starter assets."
        >
            <template #metrics>
                <AppStatusBadge status="active" label="Starter ready" />
                <AppStatusBadge status="processing" :label="today" />
            </template>
        </AppPageHeader>

        <AppBanner
            title="Admin surface baseline"
            message="This page now follows the same starter-first language as the component catalog: stat rows, sectional cards, domain widgets, and clear module entry points."
            type="success"
        />

        <div class="stat-row">
            <AppStatCard
                v-for="stat in stats"
                :key="stat.label"
                :label="stat.label"
                :value="stat.value"
                :helper="stat.helper"
                :icon="stat.icon"
                :icon-color="stat.iconColor"
                :icon-background="stat.bg"
            />
        </div>

        <div class="dashboard__body">
            <AppSectionCard title="Platform modules" subtitle="Every major starter surface is discoverable from the dashboard.">
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
            </AppSectionCard>

            <aside class="dashboard__aside">
                <AppSectionCard title="Signed in as">
                    <div class="identity">
                        <span class="identity__avatar">{{ userInitials }}</span>
                        <div class="identity__info">
                            <span class="identity__name">{{ session.user?.name }}</span>
                            <span class="identity__email">{{ session.user?.email }}</span>
                        </div>
                    </div>
                    <div class="role-list">
                        <AppStatusBadge
                            v-for="role in session.user?.roles"
                            :key="role"
                            :status="role"
                            :label="role"
                        />
                        <span v-if="!session.user?.roles?.length" class="role-none">No roles assigned</span>
                    </div>
                </AppSectionCard>

                <AppSectionCard title="Stack">
                    <ul class="stack-list">
                        <li v-for="item in stackItems" :key="item.name" class="stack-list__item">
                            <span class="stack-list__name">{{ item.name }}</span>
                            <span class="stack-list__ver">{{ item.ver }}</span>
                        </li>
                    </ul>
                </AppSectionCard>
            </aside>
        </div>

        <div class="dashboard__commerce">
            <PaymentStatusCard
                title="One-time payment"
                subtitle="Starter billing summary primitive"
                amount="R 1,499.00"
                reference="Order #RW-10027"
                status="processing"
                provider="PayFast"
                customer="Starter Owner"
                requested-at="Today, 10:40"
                settled-at="Awaiting ITN"
            />

            <SubscriptionStatusCard
                title="Recurring subscription"
                subtitle="Starter subscription surface"
                amount="R 299.00 / month"
                plan="Growth plan"
                status="active"
                billing-date="01 May 2026"
                cycles="0"
            />
        </div>

        <PaymentEventList
            title="Recent payment events"
            subtitle="Use this list for ITN history, billing audit, and support tooling."
            :events="paymentEvents"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Dashboard",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed } from 'vue';

import AppPageHeader from '../components/AppPageHeader.vue';
import AppSectionCard from '../components/AppSectionCard.vue';
import AppStatCard from '../components/AppStatCard.vue';
import AppStatusBadge from '../components/AppStatusBadge.vue';
import AppBanner from '../components/AppBanner.vue';
import PaymentEventList from '../components/PaymentEventList.vue';
import PaymentStatusCard from '../components/PaymentStatusCard.vue';
import SubscriptionStatusCard from '../components/SubscriptionStatusCard.vue';
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
    { label: 'Active roles', value: '4', helper: 'Seeded platform roles', icon: 'mdi-shield-account-outline', bg: 'rgba(0,106,74,0.08)', iconColor: 'var(--rw-600)' },
    { label: 'Permissions seeded', value: '21', helper: 'Authorization baseline', icon: 'mdi-key-outline', bg: 'rgba(180,83,9,0.08)', iconColor: 'var(--rw-amber)' },
    { label: 'Queue backend', value: 'Redis', helper: 'Horizon-ready workload', icon: 'mdi-database-outline', bg: 'rgba(3,105,161,0.08)', iconColor: '#0369a1' },
    { label: 'Storage backend', value: 'MinIO', helper: 'Media and uploads', icon: 'mdi-cloud-outline', bg: 'rgba(101,16,147,0.08)', iconColor: '#6510a3' },
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
        to: '/foundation',
        title: 'Starter foundation',
        text: 'Review the broader starter baseline, page chrome, widgets, interaction patterns, and rollout guidance.',
        icon: 'mdi-toy-brick-outline',
        color: '#0369a1',
    },
    {
        to: '/components',
        title: 'Component catalog',
        text: 'Open the live inventory of shared inputs, cards, feedback surfaces, and domain primitives.',
        icon: 'mdi-shape-outline',
        color: '#7c3aed',
    },
];

const stackItems = [
    { name: 'Laravel', ver: '13' },
    { name: 'lara-auth-suite', ver: '2' },
    { name: 'payfast-payment', ver: '1' },
    { name: 'Vue + Vuetify', ver: '3' },
    { name: 'Pinia', ver: '2' },
    { name: 'Horizon', ver: '5' },
];

const paymentEvents = [
    {
        id: 1,
        title: 'Checkout initiated',
        time: 'Today, 10:40',
        text: 'One-time order RW-10027 was sent to PayFast for customer redirect.',
        type: 'info',
    },
    {
        id: 2,
        title: 'ITN received',
        time: 'Today, 10:43',
        text: 'Provider callback validated the merchant signature and queued reconciliation.',
        type: 'warning',
    },
    {
        id: 3,
        title: 'Payment completed',
        time: 'Today, 10:45',
        text: 'Payment settled successfully and the starter payment aggregate moved to paid.',
        type: 'success',
    },
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

.stat-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.875rem;
}

.dashboard__body {
    display: grid;
    gap: 1.5rem;
    align-items: start;
}

.dashboard__commerce {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

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

@media (max-width: 860px) {
    .dashboard__commerce {
        grid-template-columns: 1fr;
    }
}
</style>
