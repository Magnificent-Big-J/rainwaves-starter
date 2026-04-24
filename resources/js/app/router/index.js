import { createRouter, createWebHistory } from 'vue-router';

import FoundationPage from '../pages/FoundationPage.vue';
import HomePage from '../pages/HomePage.vue';

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomePage,
    },
    {
        path: '/foundation',
        name: 'foundation',
        component: FoundationPage,
    },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
});
