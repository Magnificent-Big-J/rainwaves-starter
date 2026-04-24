import { defineStore } from 'pinia';

export const useAppErrorsStore = defineStore('appErrors', {
    state: () => ({
        active: null,
        lastFingerprint: '',
        lastShownAt: 0,
    }),
    actions: {
        show(payload) {
            const message = String(payload?.message || '').trim();

            if (!message) {
                return;
            }

            const color = payload?.color || 'error';
            const timeout = Number(payload?.timeout || 5000);
            const fingerprint = `${color}:${message}`;
            const now = Date.now();

            if (this.lastFingerprint === fingerprint && now - this.lastShownAt < 1000) {
                return;
            }

            this.lastFingerprint = fingerprint;
            this.lastShownAt = now;
            this.active = {
                id: now,
                message,
                color,
                timeout,
            };
        },
        clear() {
            this.active = null;
        },
    },
});
