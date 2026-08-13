import { createRouter, createWebHistory } from 'vue-router';
import { handleHotUpdate, routes } from 'vue-router/auto-routes';
import { useAppConfigStore } from '../stores/app-config';
import { useSessionStore } from '../stores/session';

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const session = useSessionStore();
    const appConfig = useAppConfigStore();

    // Both are needed before isAdminSurface/homeRoute (which read navigation.admin_roles
    // and navigation.home_routes) can be trusted, so load them together.
    await Promise.all([session.ensureLoaded(), appConfig.ensureLoaded()]);

    if (to.meta.requiresAuth && !session.isAuthenticated) {
        return '/auth/login';
    }

    if (to.meta.guestOnly && session.isAuthenticated) {
        return session.homeRoute;
    }

    if (to.path === '/' && session.isAuthenticated) {
        return session.homeRoute;
    }

    if (to.path !== '/auth/verify' && session.pendingTwoFactorRequired) {
        return '/auth/verify';
    }

    if (to.meta.adminOnly && session.activeSurface !== 'admin') {
        return session.homeRoute;
    }
});

router.afterEach((to) => {
    document.title = to.meta.title
        ? `${to.meta.title} | Rainwaves Starter`
        : 'Rainwaves Starter';
});

if (import.meta.hot) {
    handleHotUpdate(router);
}

export { router };
