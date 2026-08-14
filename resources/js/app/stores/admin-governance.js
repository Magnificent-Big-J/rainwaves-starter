import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { normalizeErrorMessage } from './auth-shared';

export const useAdminGovernanceStore = defineStore('adminGovernance', {
    state: () => ({
        rows: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 15 } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                const response = await v1(`governance/role-change-requests?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta?.pagination ?? this.meta;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async approve(requestId) {
            try {
                await v1(`governance/role-change-requests/${requestId}/approve`, { method: 'POST' });

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to approve that request.') };
            }
        },

        async reject(requestId) {
            try {
                await v1(`governance/role-change-requests/${requestId}/reject`, { method: 'POST' });

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to reject that request.') };
            }
        },
    },
});
