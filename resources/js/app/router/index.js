import { createRouter, createWebHistory } from 'vue-router';
import { handleHotUpdate, routes } from 'vue-router/auto-routes';
import { useSessionStore } from '../stores/session';

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const session = useSessionStore();

    await session.ensureLoaded();

    if (to.meta.requiresAuth && !session.isAuthenticated) {
        return '/auth/login';
    }

    if (to.meta.guestOnly && session.isAuthenticated) {
        return '/dashboard';
    }

    if (to.path === '/' && session.isAuthenticated) {
        return '/dashboard';
    }

    if (to.path !== '/auth/verify' && session.pendingTwoFactorRequired) {
        return '/auth/verify';
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
