import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { useAppErrorsStore } from './app-errors';
import { normalizeErrorMessage } from './auth-shared';

// Active *web browser* sessions (config('session.driver') === 'database'), backed by
// GET/DELETE /api/v1/sessions. Distinct from stores/notifications.js's device list —
// a cookie session never registers a Device row.
export const useSessionsStore = defineStore('activeSessions', {
    state: () => ({
        items: [],
        loading: false,
        loaded: false,
    }),
    actions: {
        async fetch() {
            this.loading = true;

            try {
                const response = await v1('sessions');
                this.items = response?.data ?? [];
                this.loaded = true;
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to load sessions.') });
            } finally {
                this.loading = false;
            }
        },
        async revoke(id) {
            try {
                await v1(`sessions/${id}`, { method: 'DELETE' });
                this.items = this.items.filter((item) => item.id !== id);
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to revoke that session.') });
            }
        },
        async revokeOthers() {
            try {
                await v1('sessions/others', { method: 'DELETE' });
                this.items = this.items.filter((item) => item.is_current);
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to revoke other sessions.') });
            }
        },
    },
});
