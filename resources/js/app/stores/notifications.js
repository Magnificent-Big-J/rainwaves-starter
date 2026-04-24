import { defineStore } from 'pinia';

const iconMap = {
    success: 'mdi-check-circle-outline',
    error: 'mdi-alert-circle-outline',
    warning: 'mdi-alert-outline',
    info: 'mdi-information-outline',
};

const colorMap = {
    success: 'success',
    error: 'error',
    warning: 'warning',
    info: 'info',
};

const fingerprintFor = (payload) => `${payload.type}:${payload.title}:${payload.message}`;

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        toasts: [],
        items: [],
        lastFingerprint: '',
        lastShownAt: 0,
        seededFor: '',
    }),
    getters: {
        unreadCount: (state) => state.items.filter((item) => !item.readAt).length,
        activeToast: (state) => state.toasts[0] ?? null,
    },
    actions: {
        pushToast(payload) {
            const message = String(payload?.message || '').trim();

            if (!message) {
                return;
            }

            const toast = {
                id: Date.now() + Math.random(),
                title: payload?.title || null,
                message,
                type: payload?.type || 'info',
                color: payload?.color || colorMap[payload?.type || 'info'] || 'info',
                icon: payload?.icon || iconMap[payload?.type || 'info'] || iconMap.info,
                timeout: Number(payload?.timeout || 5000),
                actionLabel: payload?.actionLabel || null,
            };

            const fingerprint = fingerprintFor({
                type: toast.type,
                title: toast.title || '',
                message: toast.message,
            });
            const now = Date.now();

            if (this.lastFingerprint === fingerprint && now - this.lastShownAt < 1000) {
                return;
            }

            this.lastFingerprint = fingerprint;
            this.lastShownAt = now;
            this.toasts.push(toast);
        },
        dismissToast(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
        addNotification(payload) {
            const message = String(payload?.message || '').trim();

            if (!message) {
                return;
            }

            this.items.unshift({
                id: Date.now() + Math.random(),
                title: payload?.title || 'Notification',
                message,
                type: payload?.type || 'info',
                color: payload?.color || colorMap[payload?.type || 'info'] || 'info',
                icon: payload?.icon || iconMap[payload?.type || 'info'] || iconMap.info,
                category: payload?.category || 'system',
                timeLabel: payload?.timeLabel || 'Just now',
                readAt: payload?.readAt || null,
                actionLabel: payload?.actionLabel || null,
            });
        },
        notify(payload) {
            this.addNotification(payload);
            this.pushToast(payload);
        },
        markAsRead(id) {
            const item = this.items.find((entry) => entry.id === id);

            if (item && !item.readAt) {
                item.readAt = new Date().toISOString();
            }
        },
        markAllAsRead() {
            const now = new Date().toISOString();

            this.items = this.items.map((item) => ({
                ...item,
                readAt: item.readAt || now,
            }));
        },
        clearAll() {
            this.items = [];
        },
        ensureSeeded(context = 'guest') {
            if (this.seededFor === context && this.items.length) {
                return;
            }

            this.seededFor = context;
            this.items = [
                {
                    id: 1,
                    title: context === 'customer'
                        ? 'Your customer account is ready'
                        : 'Starter seeded accounts ready',
                    message: context === 'customer'
                        ? 'Your account area can surface orders, subscriptions, invoices, and profile controls from this shell.'
                        : 'Owner, ops, and customer accounts are available for local testing.',
                    type: 'success',
                    color: 'success',
                    icon: iconMap.success,
                    category: context === 'customer' ? 'account' : 'system',
                    timeLabel: 'Today',
                    readAt: null,
                    actionLabel: context === 'customer' ? 'Open profile' : 'Review users',
                },
                {
                    id: 2,
                    title: context === 'customer'
                        ? 'Subscription billing active'
                        : 'PayFast sandbox setup',
                    message: context === 'customer'
                        ? 'Use the customer shell to surface invoices, renewals, upgrades, and billing-history notifications.'
                        : 'Configure merchant keys before testing checkout and ITN handling.',
                    type: 'warning',
                    color: 'warning',
                    icon: iconMap.warning,
                    category: 'billing',
                    timeLabel: 'Today',
                    readAt: null,
                    actionLabel: context === 'customer' ? 'View billing' : 'Open billing docs',
                },
                {
                    id: 3,
                    title: 'Two-factor protection enabled',
                    message: context === 'admin'
                        ? 'Admin starter flows should verify TOTP, email OTP, and recovery-code handling.'
                        : context === 'customer'
                            ? 'Customer account flows should keep profile, notifications, and security actions inside the customer shell.'
                        : 'Remember to verify the 2FA flows before shipping a new product baseline.',
                    type: 'info',
                    color: 'info',
                    icon: iconMap.info,
                    category: 'security',
                    timeLabel: 'Earlier',
                    readAt: context === 'guest' ? new Date().toISOString() : null,
                    actionLabel: context === 'customer' ? 'Manage security' : 'View security',
                },
            ];
        },
    },
});
