import js from '@eslint/js';
import prettierConfig from '@vue/eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    prettierConfig,
    {
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
        rules: {
            // Vue components in this codebase are named after their route/purpose
            // (dashboard.vue, notifications.vue, [...notFound].vue) rather than
            // multi-word PascalCase — that's an intentional file-based-routing
            // convention (unplugin-vue-router), not something to lint against.
            'vue/multi-word-component-names': 'off',
            'no-unused-vars': [
                'warn',
                { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' },
            ],
        },
    },
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'storage/**', 'bootstrap/cache/**'],
    },
];
