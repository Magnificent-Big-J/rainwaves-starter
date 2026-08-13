<template>
    <v-app>
        <div class="customer-shell">
            <header class="customer-topbar">
                <RouterLink to="/customer/home" class="customer-brand">
                    <span class="customer-brand__badge">{{ appConfig.brand.short_name }}</span>
                    <span class="customer-brand__text">
                        <strong>{{ appConfig.brand.name.split(' ')[0] }}</strong>
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
                    <div v-if="showTeamSwitcher" class="team-switcher">
                        <v-menu v-if="team.teams.length">
                            <template #activator="{ props: menuProps }">
                                <button class="team-switcher__button" v-bind="menuProps">
                                    <v-icon size="16" class="team-switcher__icon">mdi-account-multiple-outline</v-icon>
                                    <span class="team-switcher__name">{{ team.team?.name ?? 'Select a team' }}</span>
                                    <v-icon size="16">mdi-unfold-more-horizontal</v-icon>
                                </button>
                            </template>
                            <v-list density="compact">
                                <v-list-item
                                    v-for="teamOption in team.teams"
                                    :key="teamOption.id"
                                    :title="teamOption.name"
                                    :active="teamOption.id === team.team?.id"
                                    @click="onSwitchTeam(teamOption.id)"
                                />
                                <v-divider />
                                <v-list-item title="Create new team" prepend-icon="mdi-plus" to="/account/team" />
                            </v-list>
                        </v-menu>
                        <RouterLink v-else to="/account/team" class="team-switcher__empty">
                            <v-icon size="16">mdi-plus</v-icon>
                            Create a team
                        </RouterLink>
                    </div>

                    <AppNotificationPanel />

                    <RouterLink to="/profile" class="customer-profile">
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
import { useAppConfigStore } from '../stores/app-config';
import { useNotificationsStore } from '../stores/notifications';
import { useSessionStore } from '../stores/session';
import { useTeamStore } from '../stores/team';

const session = useSessionStore();
const appConfig = useAppConfigStore();
const notifications = useNotificationsStore();
const team = useTeamStore();
const route = useRoute();
const router = useRouter();

const hasModule = (module) => !module || appConfig.modules[module] !== false;
const showTeamSwitcher = computed(() => appConfig.modules.teams !== false);

const customerNav = computed(() =>
    (appConfig.navigation.main ?? [])
        .filter((item) => (!item.surfaces || item.surfaces.includes('customer')) && hasModule(item.module))
        .map((item) => ({ label: item.label, to: item.to }))
);

const userInitials = computed(() =>
    (session.user?.name || appConfig.brand.short_name)
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

const onSwitchTeam = async (teamId) => {
    if (teamId === team.team?.id) {
        return;
    }

    const result = await team.switchTeam(teamId);

    if (result.ok) {
        router.push('/account/team');
    }
};

watch(
    () => session.isAuthenticated,
    (authenticated) => {
        if (authenticated) {
            notifications.fetch();
            team.fetch();
            team.fetchTeams();
        }
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
    transition:
        background 0.16s ease,
        color 0.16s ease;
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

.team-switcher {
    display: inline-flex;
}

.team-switcher__button {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    max-width: 160px;
    padding: 0.4rem 0.7rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    color: var(--rw-ink);
    cursor: pointer;
    transition: background 0.12s;
}

.team-switcher__button:hover {
    background: rgba(255, 255, 255, 1);
}

.team-switcher__icon {
    flex-shrink: 0;
    opacity: 0.75;
}

.team-switcher__name {
    min-width: 0;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.team-switcher__empty {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.7rem;
    border: 1px dashed rgba(15, 23, 42, 0.16);
    border-radius: 999px;
    color: var(--rw-dim);
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    transition:
        background 0.12s,
        color 0.12s;
}

.team-switcher__empty:hover {
    background: rgba(255, 255, 255, 0.85);
    color: var(--rw-ink);
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
