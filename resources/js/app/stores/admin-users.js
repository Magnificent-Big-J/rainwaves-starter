import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

const normalizePayload = (payload) => {
    const data = { ...payload };

    if (!data.password) {
        delete data.password;
        delete data.password_confirmation;
    }

    return data;
};

export const useAdminUsersStore = defineStore('adminUsers', {
    state: () => ({
        rows: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        },
        options: {
            roles: [],
            permissions: [],
        },
        loading: false,
    }),
    actions: {
        async fetch({
            page = 1,
            perPage = 10,
            search = '',
            role = '',
            status = '',
            sortBy = '',
            sortDirection = 'asc',
        } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });

                if (search) params.set('search', search);
                if (role) params.set('role', role);
                if (status) params.set('status', status);
                if (sortBy) {
                    params.set('sort_by', sortBy);
                    params.set('sort_direction', sortDirection);
                }

                const response = await v1(`users?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta?.pagination ?? this.meta;
                this.options = response?.meta?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async archive(userId) {
            const response = await v1(`users/${userId}`, { method: 'DELETE' });

            return response?.data ?? response;
        },

        async restore(userId) {
            const response = await v1(`users/${userId}/restore`, { method: 'POST' });

            return response?.data ?? response;
        },

        // No dedicated bulk endpoint — archiving is a light, idempotent-ish write, so
        // fanning out to the single-item endpoint keeps the backend simple. A future
        // module with heavier bulk semantics (partial-failure reporting, transactions)
        // would want a real POST /bulk endpoint instead.
        async bulkArchive(userIds) {
            const results = await Promise.allSettled(userIds.map((id) => this.archive(id)));

            return {
                succeeded: results.filter((r) => r.status === 'fulfilled').length,
                failed: results.filter((r) => r.status === 'rejected').length,
            };
        },

        async create(payload) {
            this.loading = true;

            try {
                const response = await v1('users', {
                    method: 'POST',
                    body: normalizePayload(payload),
                });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(userId, payload) {
            this.loading = true;

            try {
                const response = await v1(`users/${userId}`, {
                    method: 'PATCH',
                    body: normalizePayload(payload),
                });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },
    },
});
