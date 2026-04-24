<template>
    <v-app>
        <div class="customer-shell">
            <header class="customer-topbar">
                <RouterLink to="/customer/home" class="customer-brand">
                    <span class="customer-brand__badge">RW</span>
                    <span class="customer-brand__text">
                        <strong>Rainwaves</strong>
                        <span>Customer</span>
                    </span>
                </RouterLink>

                <nav class="customer-nav">
                    <RouterLink
                        v-for="item in customerNav"
                        :key="item.to"
                        :to="item.to"
                        :class="['customer-nav__link', isActive(item.to) && 'customer-nav__link--active']"
                    >
                        {{ item.label }}
                    </RouterLink>
                </nav>

                <div class="customer-actions">
                    <AppNotificationPanel />

                    <RouterLink to="/auth/profile" class="customer-profile">
                        <span class="customer-profile__avatar">{{ userInitials }}</span>
                        <span class="customer-profile__meta">
                            <span class="customer-profile__name">{{ session.user?.name }}</span>
                            <span class="customer-profile__role">Customer account</span>
                        </span>
                    </RouterLink>

                    <v-btn variant="text" size="small" icon="mdi-logout" @click="logout" />
                </div>
            </header>

            <main class="customer-main">
                <RouterView />
            </main>
        </div>
    </v-app>
</template>

<script setup>
import { computed, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

import AppNotificationPanel from '../components/AppNotificationPanel.vue';
import { useNotificationsStore } from '../stores/notifications';
import { useSessionStore } from '../stores/session';

const session = useSessionStore();
const notifications = useNotificationsStore();
const route = useRoute();
const router = useRouter();

const customerNav = [
    { label: 'Home', to: '/customer/home' },
    { label: 'Profile', to: '/auth/profile' },
];

const userInitials = computed(() =>
    (session.user?.name || 'RW')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('')
);

const isActive = (target) => route.path.startsWith(target);

const logout = async () => {
    await session.logout();
    router.push('/auth/login');
};

watch(
    () => session.activeSurface,
    (surface) => {
        notifications.ensureSeeded(surface === 'customer' ? 'customer' : 'guest');
    },
    { immediate: true }
);
</script>

<style scoped>
.customer-shell {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(0, 135, 95, 0.14), transparent 28%),
        linear-gradient(180deg, #f4fbf8 0%, #eef6ff 100%);
}

.customer-topbar {
    position: sticky;
    top: 0;
    z-index: 50;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.86);
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(18px);
}

.customer-brand {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--rw-ink);
}

.customer-brand__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 14px;
    background: linear-gradient(145deg, var(--rw-700), var(--rw-500));
    color: white;
    font-weight: 800;
}

.customer-brand__text {
    display: grid;
    line-height: 1.1;
    font-size: 0.84rem;
}

.customer-brand__text strong {
    font-size: 0.92rem;
}

.customer-brand__text span {
    color: var(--rw-dim);
}

.customer-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
    flex-wrap: wrap;
}

.customer-nav__link {
    padding: 0.65rem 0.9rem;
    border-radius: 999px;
    color: var(--rw-dim);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.92rem;
    transition: background 0.16s ease, color 0.16s ease;
}

.customer-nav__link:hover,
.customer-nav__link--active {
    background: rgba(0, 106, 74, 0.09);
    color: var(--rw-700);
}

.customer-actions {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
}

.customer-profile {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    padding: 0.45rem 0.7rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(15, 23, 42, 0.08);
    text-decoration: none;
    color: var(--rw-ink);
}

.customer-profile__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 999px;
    background: rgba(0, 106, 74, 0.12);
    color: var(--rw-700);
    font-size: 0.8rem;
    font-weight: 800;
}

.customer-profile__meta {
    display: grid;
    line-height: 1.1;
}

.customer-profile__name {
    font-size: 0.85rem;
    font-weight: 700;
}

.customer-profile__role {
    font-size: 0.74rem;
    color: var(--rw-dim);
}

.customer-main {
    max-width: 1180px;
    margin: 0 auto;
    padding: 2rem 1.25rem 4rem;
}

@media (max-width: 980px) {
    .customer-topbar {
        grid-template-columns: 1fr;
        justify-items: start;
        padding: 0.9rem 1rem;
    }

    .customer-nav {
        justify-content: flex-start;
    }

    .customer-actions {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 640px) {
    .customer-main {
        padding: 1.5rem 0.9rem 3rem;
    }

    .customer-profile__meta {
        display: none;
    }

    .customer-actions {
        gap: 0.5rem;
    }
}

@media (max-width: 420px) {
    .customer-topbar {
        padding-inline: 0.75rem;
    }

    .customer-nav {
        width: 100%;
    }
}
</style>
