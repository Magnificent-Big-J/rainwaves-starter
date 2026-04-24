import { createRouter, createWebHistory } from 'vue-router';
import { handleHotUpdate, routes } from 'vue-router/auto-routes';

const router = createRouter({
    history: createWebHistory(),
    routes,
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
