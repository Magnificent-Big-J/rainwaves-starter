import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { useAppErrorsStore } from './app-errors';
import { normalizeErrorMessage } from './auth-shared';
import { startPayFastCheckout } from '../utils/payfast-checkout';

export const useBillingStore = defineStore('billing', {
    state: () => ({
        latestPayment: null,
        latestSubscription: null,
        recentEvents: [],
        loading: false,
        loaded: false,
        checkingOut: false,
    }),
    actions: {
        async fetch() {
            this.loading = true;

            try {
                const response = await v1('billing');
                const data = response?.data ?? {};

                this.latestPayment = data.latest_payment ?? null;
                this.latestSubscription = data.latest_subscription ?? null;
                this.recentEvents = data.recent_events ?? [];
                this.loaded = true;
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to load billing summary.') });
            } finally {
                this.loading = false;
            }
        },
        async checkout(mode, payload) {
            this.checkingOut = true;

            try {
                // Navigates the browser away to PayFast on success — nothing to await.
                await startPayFastCheckout(mode, payload);

                return { ok: true };
            } catch (error) {
                this.checkingOut = false;

                return { ok: false, message: error?.message || 'Unable to start checkout.' };
            }
        },
    },
});
