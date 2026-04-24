import { defineStore } from 'pinia';
import { useNotificationsStore } from './notifications';

export const useAppErrorsStore = defineStore('appErrors', {
    state: () => ({
        active: null,
    }),
    actions: {
        show(payload) {
            const message = String(payload?.message || '').trim();

            if (!message) {
                return;
            }

            const notifications = useNotificationsStore();
            const active = {
                id: Date.now(),
                title: payload?.title || 'Something went wrong',
                message,
                color: payload?.color || 'error',
                type: payload?.type || 'error',
                timeout: Number(payload?.timeout || 5000),
            };

            this.active = active;
            notifications.notify(active);
        },
        clear() {
            this.active = null;
        },
    },
});
