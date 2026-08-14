import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { normalizeErrorMessage } from './auth-shared';
import { useSessionStore } from './session';

// Cached per authenticated user (not forever, unlike session/app-config) — legal status
// is per-user, so a second login in the same tab (after a logout) must refetch rather
// than trust the previous user's cached outstanding-documents state.
export const useLegalStore = defineStore('legal', {
    state: () => ({
        loaded: false,
        loadedForUserId: null,
        loading: false,
        documents: [],
    }),
    getters: {
        hasOutstandingDocuments: (state) => state.documents.some((doc) => doc.accepted_version !== doc.version),
    },
    actions: {
        async ensureLoaded() {
            const userId = useSessionStore().user?.id ?? null;

            if (this.loading || (this.loaded && this.loadedForUserId === userId)) {
                return;
            }

            this.loading = true;

            try {
                const response = await v1('legal/status');
                this.documents = response?.data?.documents ?? [];
            } catch (_error) {
                // Fail open, matching app-config's own precedent — a transient fetch
                // failure shouldn't lock a user out of the app.
                this.documents = [];
            } finally {
                this.loaded = true;
                this.loadedForUserId = userId;
                this.loading = false;
            }
        },
        async accept(documents) {
            try {
                const response = await v1('legal/accept', { method: 'POST', body: { documents } });
                this.documents = response?.data?.documents ?? this.documents;

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to accept those documents.') };
            }
        },
    },
});
