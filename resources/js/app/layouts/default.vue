<template>
    <v-app>
        <div :class="['shell', session.isAuthenticated && 'shell--auth']">

            <!-- ── Authenticated sidebar ──────────────────────── -->
            <aside v-if="session.isAuthenticated" :class="['sidebar', mobileOpen && 'sidebar--open']">
                <div class="sidebar__inner">

                    <div class="sidebar__brand">
                        <RouterLink to="/" class="brand-mark" @click="mobileOpen = false">
                            <span class="brand-mark__badge">
                                <span class="brand-mark__letters">RW</span>
                            </span>
                            <div class="brand-mark__text">
                                <span class="brand-mark__name">Rainwaves</span>
                                <span class="brand-mark__sub">Starter</span>
                            </div>
                        </RouterLink>
                    </div>

                    <nav class="sidebar__nav">
                        <div class="nav-group">
                            <NavItem
                                v-for="item in mainNav"
                                :key="item.to"
                                :item="item"
                                @click="mobileOpen = false"
                            />
                        </div>

                        <template v-if="isAdmin">
                            <div class="nav-divider">
                                <span class="nav-divider__label">Admin</span>
                            </div>
                            <div class="nav-group">
                                <NavItem
                                    v-for="item in adminNav"
                                    :key="item.to"
                                    :item="item"
                                    @click="mobileOpen = false"
                                />
                            </div>
                        </template>
                    </nav>

                    <div class="sidebar__footer">
                        <RouterLink to="/auth/profile" class="user-card" @click="mobileOpen = false">
                            <span class="user-card__avatar">{{ userInitials }}</span>
                            <div class="user-card__info">
                                <span class="user-card__name">{{ session.user?.name }}</span>
                                <span class="user-card__email">{{ session.user?.email }}</span>
                            </div>
                        </RouterLink>
                        <button class="logout-btn" title="Sign out" @click="logout">
                            <v-icon size="17">mdi-logout</v-icon>
                        </button>
                    </div>

                </div>
            </aside>

            <!-- ── Mobile overlay ─────────────────────────────── -->
            <div
                v-if="session.isAuthenticated && mobileOpen"
                class="sidebar-overlay"
                @click="mobileOpen = false"
            />

            <!-- ── App body ───────────────────────────────────── -->
            <div class="app-body">

                <!-- Authenticated topbar -->
                <header v-if="session.isAuthenticated" class="topbar">
                    <button class="topbar__burger" @click="mobileOpen = !mobileOpen">
                        <v-icon size="20">mdi-menu</v-icon>
                    </button>
                    <div class="topbar__breadcrumb">
                        <span v-if="pageTitle" class="topbar__page">{{ pageTitle }}</span>
                    </div>
                    <div class="topbar__actions">
                        <AppNotificationPanel />
                    </div>
                </header>

                <!-- Guest topbar -->
                <header v-else class="guest-bar">
                    <RouterLink to="/" class="guest-brand">
                        <span class="guest-brand__badge">RW</span>
                        <span class="guest-brand__name">Rainwaves <em>Starter</em></span>
                    </RouterLink>
                    <nav class="guest-nav">
                        <RouterLink to="/about" class="guest-nav__link">About</RouterLink>
                        <RouterLink to="/auth/register" class="guest-nav__link">Register</RouterLink>
                        <RouterLink to="/auth/login" class="guest-nav__cta">Sign in</RouterLink>
                    </nav>
                </header>

                <main class="app-main">
                    <RouterView />
                </main>

            </div>
        </div>
    </v-app>
</template>

<script setup>
import { computed, defineComponent, h, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

import AppNotificationPanel from '../components/AppNotificationPanel.vue';
import { useSessionStore } from '../stores/session';
import { useNotificationsStore } from '../stores/notifications';

const session = useSessionStore();
const notifications = useNotificationsStore();
const router = useRouter();
const route = useRoute();
const mobileOpen = ref(false);

const mainNav = [
    { label: 'Dashboard', to: '/dashboard',    icon: 'mdi-view-dashboard-outline' },
    { label: 'Profile',   to: '/auth/profile', icon: 'mdi-account-circle-outline' },
];

const adminNav = [
    { label: 'Users', to: '/admin/users', icon: 'mdi-account-group-outline' },
];

const isAdmin = computed(() =>
    session.user?.roles?.some((r) => ['super-admin', 'admin'].includes(r))
);

const userInitials = computed(() =>
    (session.user?.name || 'RW')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || '')
        .join('')
);

const pageTitle = computed(() => route.meta?.title ?? null);

const logout = async () => {
    await session.logout();
    router.push('/auth/login');
};

watch(
    () => session.activeSurface,
    (surface) => {
        notifications.ensureSeeded(surface === 'admin' ? 'admin' : 'guest');
    },
    { immediate: true }
);

// ── Inline NavItem to keep this file self-contained ──
const NavItem = defineComponent({
    props: { item: Object },
    emits: ['click'],
    setup(props, { emit }) {
        const route = useRoute();
        const isActive = computed(() => {
            if (props.item.to === '/dashboard') return route.path === '/dashboard';
            return route.path.startsWith(props.item.to);
        });

        return () =>
            h(
                RouterLink,
                {
                    to: props.item.to,
                    class: ['nav-item', isActive.value && 'nav-item--active'],
                    onClick: () => emit('click'),
                },
                () => [
                    h('span', { class: 'nav-item__icon' }, [
                        h('i', { class: `mdi ${props.item.icon}` }),
                    ]),
                    h('span', { class: 'nav-item__label' }, props.item.label),
                ]
            );
    },
});
</script>

<style scoped>
/* ── Shell ─────────────────────────────────────────── */
.shell {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.shell--auth {
    flex-direction: row;
}

/* ── Sidebar ───────────────────────────────────────── */
.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: var(--rw-sidebar);
    background: var(--rw-surface);
    border-right: 1px solid var(--rw-border);
    z-index: 200;
    transform: translateX(-100%);
    transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.sidebar--open {
    transform: translateX(0);
    box-shadow: var(--rw-shadow-xl);
}

.sidebar__inner {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

/* Brand ────────────────────────────────────────────── */
.sidebar__brand {
    padding: 1.25rem 1rem 1rem;
    border-bottom: 1px solid var(--rw-border);
}

.brand-mark {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--rw-ink);
}

.brand-mark__badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 10px;
    background: linear-gradient(145deg, var(--rw-700), var(--rw-500));
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 106, 74, 0.4);
}

.brand-mark__letters {
    font-size: 0.8rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: 0.05em;
}

.brand-mark__text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.brand-mark__name {
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--rw-ink);
    letter-spacing: -0.01em;
}

.brand-mark__sub {
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--rw-muted);
    text-transform: uppercase;
    letter-spacing: 0.12em;
}

/* Navigation ───────────────────────────────────────── */
.sidebar__nav {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem 0.625rem;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.nav-group {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

:deep(.nav-item) {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.55rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--rw-muted);
    text-decoration: none;
    transition: background 0.12s, color 0.12s;
    position: relative;
}

:deep(.nav-item:hover) {
    background: var(--rw-surface-2);
    color: var(--rw-ink-2);
}

:deep(.nav-item--active) {
    background: var(--rw-50);
    color: var(--rw-700);
    font-weight: 600;
}

:deep(.nav-item--active)::before {
    content: '';
    position: absolute;
    left: 0;
    top: 20%;
    bottom: 20%;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--rw-600);
}

:deep(.nav-item__icon) {
    display: flex;
    font-size: 1.05rem;
    opacity: 0.9;
}

.nav-divider {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 0.875rem 0.375rem;
}

.nav-divider__label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--rw-dim);
}

/* Footer ───────────────────────────────────────────── */
.sidebar__footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 0.75rem;
    border-top: 1px solid var(--rw-border);
    background: var(--rw-surface);
}

.user-card {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    flex: 1;
    min-width: 0;
    text-decoration: none;
    border-radius: 0.5rem;
    padding: 0.35rem 0.5rem;
    transition: background 0.12s;
}

.user-card:hover {
    background: var(--rw-surface-2);
}

.user-card__avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--rw-700), var(--rw-500));
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    flex-shrink: 0;
}

.user-card__info {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.3;
}

.user-card__name {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--rw-ink);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-card__email {
    font-size: 0.72rem;
    color: var(--rw-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: none;
    background: none;
    border-radius: 0.4rem;
    color: var(--rw-muted);
    cursor: pointer;
    transition: background 0.12s, color 0.12s;
    flex-shrink: 0;
}

.logout-btn:hover {
    background: #fee2e2;
    color: #b91c1c;
}

/* Mobile overlay ───────────────────────────────────── */
.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(13, 27, 42, 0.4);
    backdrop-filter: blur(2px);
    z-index: 199;
}

/* App body ─────────────────────────────────────────── */
.app-body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 0;
    min-height: 100vh;
}

/* Topbar ───────────────────────────────────────────── */
.topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    height: var(--rw-topbar);
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0 1.5rem;
    background: rgba(242, 239, 232, 0.88);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--rw-border);
}

.topbar__burger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: none;
    border: none;
    border-radius: 0.4rem;
    cursor: pointer;
    color: var(--rw-muted);
    transition: background 0.12s, color 0.12s;
}

.topbar__burger:hover {
    background: var(--rw-border);
    color: var(--rw-ink);
}

.topbar__breadcrumb {
    flex: 1;
}

.topbar__page {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--rw-ink-2);
}

.topbar__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Guest bar ─────────────────────────────────────────── */
.guest-bar {
    height: var(--rw-topbar);
    display: flex;
    align-items: center;
    padding: 0 2rem;
    background: rgba(242, 239, 232, 0.92);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--rw-border);
    position: sticky;
    top: 0;
    z-index: 100;
}

.guest-brand {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    text-decoration: none;
    color: var(--rw-ink);
}

.guest-brand__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
    background: linear-gradient(145deg, var(--rw-700), var(--rw-500));
    color: #fff;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.05em;
}

.guest-brand__name {
    font-size: 0.925rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.guest-brand__name em {
    font-style: normal;
    font-weight: 400;
    color: var(--rw-muted);
}

.guest-nav {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: auto;
}

.guest-nav__link {
    padding: 0.4rem 0.875rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--rw-muted);
    border-radius: 0.5rem;
    transition: background 0.12s, color 0.12s;
}

.guest-nav__link:hover {
    background: var(--rw-border);
    color: var(--rw-ink);
}

.guest-nav__cta {
    padding: 0.4rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--rw-700);
    background: var(--rw-50);
    border-radius: 0.5rem;
    border: 1px solid var(--rw-100);
    transition: background 0.12s, border-color 0.12s;
}

.guest-nav__cta:hover {
    background: var(--rw-100);
}

/* App main ─────────────────────────────────────────── */
.app-main {
    flex: 1;
}

/* Desktop: sidebar always visible ──────────────────── */
@media (min-width: 960px) {
    .sidebar {
        transform: translateX(0);
        position: sticky;
        top: 0;
        height: 100vh;
        flex-shrink: 0;
    }

    .topbar__burger {
        display: none;
    }

    .app-body {
        max-width: calc(100vw - var(--rw-sidebar));
    }
}
</style>
