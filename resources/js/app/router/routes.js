import FoundationPage from '../pages/FoundationPage.vue';
import HomePage from '../pages/HomePage.vue';

export const appRoutes = [
    {
        path: '/',
        name: 'home',
        component: HomePage,
        meta: {
            title: 'Home',
        },
    },
    {
        path: '/foundation',
        name: 'foundation',
        component: FoundationPage,
        meta: {
            title: 'Foundation',
        },
    },
];
