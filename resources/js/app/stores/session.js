import { defineStore } from 'pinia';

import { api } from '../utils/api';
import {
    csrfCookie,
    extractUserPayload,
    getXsrfToken,
    loadPendingTwoFactorState,
    normalizeErrorMessage,
    persistPendingTwoFactorState,
    SESSION_BASE,
} from './auth-shared';
import { useAppErrorsStore } from './app-errors';

export const useSessionStore = defineStore('sessionAuth', {
    state: () => {
        const pending = loadPendingTwoFactorState();

        return {
            user: null,
            initialized: false,
            loading: false,
            pendingTwoFactorRequired: pending.required,
            pendingTwoFactorChannel: pending.channel,
        };
    },
    getters: {
        isAuthenticated: (state) => Boolean(state.user),
    },
    actions: {
        setPendingTwoFactor(required, channel = null) {
            this.pendingTwoFactorRequired = required;
            this.pendingTwoFactorChannel = channel;
            persistPendingTwoFactorState(required, channel);
        },
        async login({ email, password }) {
            this.loading = true;
            this.setPendingTwoFactor(false);

            try {
                await csrfCookie();

                const response = await api(`${SESSION_BASE}/login`, {
                    method: 'POST',
                    body: { email, password },
                    headers: {
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                });

                if (response?.status === '2fa_required') {
                    this.user = null;
                    this.initialized = true;
                    this.setPendingTwoFactor(true, response?.two_factor?.channel ?? null);

                    return response;
                }

                this.user = extractUserPayload(response);
                this.initialized = true;
                this.setPendingTwoFactor(false);

                return response;
            } catch (error) {
                useAppErrorsStore().show({
                    message: normalizeErrorMessage(error, 'Unable to sign in.'),
                });

                throw error;
            } finally {
                this.loading = false;
            }
        },
        async fetchUser() {
            try {
                const response = await api(`${SESSION_BASE}/me`);

                this.user = extractUserPayload(response);
                this.setPendingTwoFactor(false);
            } catch (_error) {
                this.user = null;
            } finally {
                this.initialized = true;
            }
        },
        async ensureLoaded() {
            if (this.initialized) {
                return;
            }

            await this.fetchUser();
        },
        async logout() {
            try {
                await csrfCookie();
                await api(`${SESSION_BASE}/logout`, {
                    method: 'POST',
                    headers: {
                        'X-XSRF-TOKEN': getXsrfToken(),
                    },
                });
            } finally {
                this.user = null;
                this.initialized = true;
                this.setPendingTwoFactor(false);
            }
        },
        setUser(user) {
            this.user = user;
        },
    },
});
