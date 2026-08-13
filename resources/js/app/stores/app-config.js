import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

// RS-101/RS-102: brand + navigation + feature flags come from the backend
// (config/app-brand.php, config/features.php, config/navigation.php) instead of
// being hardcoded per layout. Fetched once and shared across every layout/page —
// call ensureLoaded() before reading state (App.vue does this on mount).
export const useAppConfigStore = defineStore('appConfig', {
    state: () => ({
        loaded: false,
        loading: false,
        brand: {
            name: 'Rainwaves Starter',
            short_name: 'RW',
            tagline: '',
            support_email: '',
            legal: { terms_url: '/legal/terms', privacy_url: '/legal/privacy' },
            footer: 'Rainwaves Starter',
        },
        features: { show_showcase_pages: false },
        navigation: {
            admin_roles: [],
            home_routes: { admin: '/dashboard', customer: '/customer/home', guest: '/' },
            main: [],
            admin: [],
            showcase: [],
            guest: [],
            legal: [],
        },
        environment: 'production',
        // RS-301: safe fallback is "enabled" — a transient web-config fetch failure
        // shouldn't hide real functionality that's actually there.
        modules: { billing: true },
    }),
    actions: {
        async ensureLoaded() {
            if (this.loaded || this.loading) {
                return;
            }

            this.loading = true;

            try {
                const response = await v1('web-config');
                const data = response?.data ?? {};

                if (data.brand) this.brand = data.brand;
                if (data.features) this.features = data.features;
                if (data.navigation) this.navigation = data.navigation;
                if (data.environment) this.environment = data.environment;
                if (data.modules) this.modules = data.modules;

                this.loaded = true;
            } catch (_error) {
                // Fall back to the built-in defaults above — the shell should still
                // render (with generic branding/nav) rather than hard-fail the app.
                this.loaded = true;
            } finally {
                this.loading = false;
            }
        },
    },
});
