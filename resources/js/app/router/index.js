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

    // RS-106: showcase pages (component catalogue, foundation, PayFast browser test,
    // about) don't belong in a shipped product's route table. Rather than removing
    // them from the build, gate them behind config/features.php `show_showcase_pages`
    // and the environment they declare — a hit outside that falls through to the
    // catch-all not-found page rather than a broken/half-working demo.
    if (to.meta.showcase && !appConfig.features.show_showcase_pages) {
        return '/showcase-disabled';
    }

    if (to.meta.environments && !to.meta.environments.includes(appConfig.environment)) {
        return '/showcase-disabled';
    }
});

router.afterEach((to) => {
    const brandName = useAppConfigStore().brand.name;

    document.title = to.meta.title ? `${to.meta.title} | ${brandName}` : brandName;
});

if (import.meta.hot) {
    handleHotUpdate(router);
}

export { router };
