import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config.js';

export default mergeConfig(
    viteConfig,
    defineConfig({
        test: {
            environment: 'happy-dom',
            globals: true,
            include: ['resources/js/**/*.spec.js'],
            setupFiles: ['resources/js/app/test-setup.js'],
            // Without this, Vitest's default SSR module loader treats `vuetify` as an
            // external Node package and skips Vite's transform pipeline for it — the
            // per-component CSS files vite-plugin-vuetify's autoImport injects
            // (e.g. VPagination.css) then hit raw Node ESM resolution, which doesn't
            // know how to import a .css file, and the whole suite fails to even load.
            server: {
                deps: {
                    inline: ['vuetify'],
                },
            },
        },
    })
);
