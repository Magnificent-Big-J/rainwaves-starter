<template>
    <v-app>
        <v-navigation-drawer
            v-if="session.isAuthenticated"
            v-model="drawer"
            :rail="rail"
            permanent
            class="app-nav"
        >
            <div class="nav-brand" :class="{ 'nav-brand--rail': rail }">
                <RouterLink to="/" class="nav-brand__link">
                    <span class="nav-brand__badge">RW</span>
                    <Transition name="fade">
                        <div v-if="!rail" class="nav-brand__copy">
                            <div class="nav-brand__title">Rainwaves</div>
                            <div class="nav-brand__sub">Starter</div>
                        </div>
                    </Transition>
                </RouterLink>
                <v-btn
                    :icon="rail ? 'mdi-chevron-right' : 'mdi-chevron-left'"
                    variant="text"
                    size="small"
                    class="nav-brand__toggle"
                    @click="rail = !rail"
                />
            </div>

            <v-divider />

            <v-list nav density="compact" class="nav-list">
                <v-list-item
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    :prepend-icon="item.icon"
                    :title="item.label"
                    rounded="xl"
                    active-class="nav-item--active"
                />

                <template v-if="isAdmin">
                    <v-divider class="my-2" />
                    <v-list-subheader v-if="!rail" class="nav-section">Admin</v-list-subheader>
                    <v-list-item
                        v-for="item in adminItems"
                        :key="item.to"
                        :to="item.to"
                        :prepend-icon="item.icon"
                        :title="item.label"
                        rounded="xl"
                        active-class="nav-item--active"
                    />
                </template>
            </v-list>

            <template #append>
                <v-divider />
                <div class="nav-footer">
                    <RouterLink to="/auth/profile" class="nav-user" :title="session.user?.name">
                        <v-avatar size="32" color="primary" variant="tonal">
                            <v-img
                                v-if="session.user?.avatar_url"
                                :src="session.user.avatar_url"
                                :alt="session.user?.name"
                                cover
                            />
                            <span v-else class="nav-user__initials">{{ userInitials }}</span>
                        </v-avatar>
                        <Transition name="fade">
                            <div v-if="!rail" class="nav-user__info">
                                <div class="nav-user__name">{{ session.user?.name }}</div>
                                <div class="nav-user__email">{{ session.user?.email }}</div>
                            </div>
                        </Transition>
                    </RouterLink>
                    <v-btn
                        icon="mdi-logout"
                        variant="text"
                        size="small"
                        title="Sign out"
                        @click="logout"
                    />
                </div>
            </template>
        </v-navigation-drawer>

        <v-app-bar v-if="session.isAuthenticated" flat class="app-bar" :elevation="0">
            <v-app-bar-nav-icon class="d-md-none" @click="drawer = !drawer" />
            <v-breadcrumbs v-if="breadcrumb" :items="[{ title: breadcrumb }]" class="app-bar__crumb" />
            <v-spacer />
            <AppToastHost />
        </v-app-bar>

        <v-app-bar v-else flat class="guest-bar">
            <RouterLink to="/" class="guest-brand">
                <span class="guest-brand__badge">RW</span>
                <div>
                    <div class="guest-brand__title">Rainwaves Starter</div>
                    <div class="guest-brand__sub">Laravel 13 · Vue · Vuetify</div>
                </div>
            </RouterLink>
            <v-spacer />
            <v-btn variant="text" rounded="xl" to="/auth/login">Sign in</v-btn>
        </v-app-bar>

        <v-main class="app-main">
            <RouterView />
        </v-main>
    </v-app>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppToastHost from '../components/AppToastHost.vue';
import { useSessionStore } from '../stores/session';

const session = useSessionStore();
const router = useRouter();
const route = useRoute();

const drawer = ref(true);
const rail = ref(false);

const navItems = [
    { label: 'Dashboard', to: '/', icon: 'mdi-view-dashboard-outline' },
    { label: 'Profile', to: '/auth/profile', icon: 'mdi-account-circle-outline' },
];

const adminItems = [
    { label: 'Users', to: '/admin/users', icon: 'mdi-account-group-outline' },
];

const isAdmin = computed(() =>
    session.user?.roles?.some((r) => ['super-admin', 'admin'].includes(r))
);

const userInitials = computed(() =>
    (session.user?.name || 'RS')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || '')
        .join('')
);

const breadcrumb = computed(() => route.meta?.title ?? null);

const logout = async () => {
    await session.logout();
    router.push('/auth/login');
};
</script>

<style scoped>
.app-nav {
    background: rgba(255, 253, 248, 0.96) !important;
    border-inline-end: 1px solid rgba(17, 34, 51, 0.08) !important;
    backdrop-filter: blur(12px);
}

.nav-brand {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0.75rem 0.75rem;
    gap: 0.5rem;
    min-height: 64px;
}

.nav-brand--rail {
    flex-direction: column;
    justify-content: center;
    padding: 0.75rem 0;
    gap: 0.35rem;
}

.nav-brand__link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
}

.nav-brand__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #006a52, #0e8b6c);
    color: white;
    font-weight: 700;
    font-size: 0.8rem;
    letter-spacing: 0.06em;
    flex-shrink: 0;
}

.nav-brand__title {
    font-weight: 800;
    font-size: 0.95rem;
    line-height: 1.2;
}

.nav-brand__sub {
    font-size: 0.75rem;
    color: var(--starter-muted);
}

.nav-brand__toggle {
    flex-shrink: 0;
}

.nav-list {
    padding: 0.5rem;
}

.nav-section {
    font-size: 0.7rem !important;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--starter-muted) !important;
    padding-inline: 0.5rem;
}

:deep(.nav-item--active) {
    background: rgba(0, 106, 82, 0.1) !important;
    color: var(--starter-accent) !important;
}

.nav-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    gap: 0.5rem;
}

.nav-user {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    text-decoration: none;
    color: inherit;
    flex: 1;
    min-width: 0;
    overflow: hidden;
}

.nav-user__initials {
    font-size: 0.75rem;
    font-weight: 700;
}

.nav-user__info {
    min-width: 0;
    overflow: hidden;
}

.nav-user__name {
    font-size: 0.875rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nav-user__email {
    font-size: 0.75rem;
    color: var(--starter-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.app-bar {
    background: rgba(247, 243, 234, 0.72) !important;
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(17, 34, 51, 0.06) !important;
}

.app-bar__crumb {
    padding: 0;
    font-size: 0.875rem;
}

.app-main {
    background: transparent;
}

.guest-bar {
    padding-inline: 1.25rem;
    background: rgba(247, 243, 234, 0.86) !important;
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(17, 34, 51, 0.08) !important;
}

.guest-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
}

.guest-brand__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #006a52, #0e8b6c);
    color: white;
    font-weight: 700;
    font-size: 0.8rem;
}

.guest-brand__title {
    font-weight: 700;
    font-size: 0.95rem;
}

.guest-brand__sub {
    font-size: 0.75rem;
    color: var(--starter-muted);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
