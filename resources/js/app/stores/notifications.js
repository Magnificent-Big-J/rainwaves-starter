import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

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

const relativeTime = (iso) => {
    if (!iso) return '';

    const diffMs = Date.now() - new Date(iso).getTime();
    const minutes = Math.round(diffMs / 60000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;

    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours}h ago`;

    const days = Math.round(hours / 24);
    if (days < 7) return `${days}d ago`;

    return new Date(iso).toLocaleDateString();
};

// Maps the real GET /api/v1/notifications payload (App\Http\Resources\NotificationResource:
// id, type, title, body, route, params, read_at, created_at) onto the shape
// AppNotificationItem.vue renders (icon/message/category/timeLabel/actionLabel/readAt).
// `type` is a free-form string set by whatever App\Notifications\AppNotification
// subclass fired it (see CLAUDE.md "Notifications") — anything unrecognised falls
// back to the generic "info" treatment rather than a broken icon.
export const toDisplayItem = (raw) => ({
    id: raw.id,
    title: raw.title || 'Notification',
    message: raw.body || '',
    type: colorMap[raw.type] ? raw.type : 'info',
    color: colorMap[raw.type] || colorMap.info,
    icon: iconMap[raw.type] || iconMap.info,
    category: raw.type || 'system',
    timeLabel: relativeTime(raw.created_at),
    readAt: raw.read_at,
    // route/params are the mobile deep-link contract (a named mobile route, not a
    // web path) — kept for parity with the API response but not used for web
    // navigation. See AppNotificationPanel.vue.
    route: raw.route,
    params: raw.params ?? {},
});

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        toasts: [],
        items: [],
        unreadCount: 0,
        loading: false,
        loaded: false,
        pagination: null,
        lastFingerprint: '',
        lastShownAt: 0,
    }),
    getters: {
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
        notify(payload) {
            this.pushToast(payload);
        },
        async fetch({ unread = false, page = 1, perPage = 20 } = {}) {
            this.loading = true;

            try {
                const response = await v1('notifications', {
                    params: { unread: unread ? 1 : undefined, page, per_page: perPage },
                });

                this.items = (response?.data ?? []).map(toDisplayItem);
                this.unreadCount = response?.meta?.unread_count ?? 0;
                this.pagination = response?.meta?.pagination ?? null;
                this.loaded = true;
            } catch (_error) {
                // Leave whatever was previously loaded in place; the panel/page can
                // still render a stale list rather than hard-failing.
            } finally {
                this.loading = false;
            }
        },
        async ensureLoaded() {
            if (this.loaded || this.loading) {
                return;
            }

            await this.fetch();
        },
        async markRead(id) {
            const item = this.items.find((entry) => entry.id === id);

            if (item?.readAt) {
                return;
            }

            try {
                const response = await v1(`notifications/${id}/read`, { method: 'POST' });

                if (item) {
                    item.readAt = new Date().toISOString();
                }

                this.unreadCount = response?.meta?.unread_count ?? Math.max(0, this.unreadCount - 1);
            } catch (_error) {
                // No-op — the item stays unread and the count stays accurate.
            }
        },
        async markAllRead() {
            try {
                await v1('notifications/read-all', { method: 'POST' });

                const now = new Date().toISOString();
                this.items = this.items.map((item) => ({ ...item, readAt: item.readAt || now }));
                this.unreadCount = 0;
            } catch (_error) {
                // No-op.
            }
        },
    },
});
