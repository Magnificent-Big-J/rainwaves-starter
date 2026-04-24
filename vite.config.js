import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import VueRouter from 'vue-router/vite';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import vuetify from 'vite-plugin-vuetify';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app/main.js'],
            refresh: true,
        }),
        VueRouter({
            routesFolder: 'resources/js/app/pages',
            dts: 'resources/js/app/typed-router.d.ts',
        }),
        vue(),
        Components({
            dirs: ['resources/js/app/components'],
            dts: false,
        }),
        vuetify({
            autoImport: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
