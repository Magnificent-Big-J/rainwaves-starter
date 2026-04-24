import { defineStore } from 'pinia';

import { api } from '../utils/api';

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
        async fetch({ page = 1, perPage = 10, search = '', role = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });

                if (search) params.set('search', search);
                if (role) params.set('role', role);

                const response = await api(`/api/v1/users?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;
                this.options = response?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async create(payload) {
            this.loading = true;

            try {
                const response = await api('/api/v1/users', {
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
                const response = await api(`/api/v1/users/${userId}`, {
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
