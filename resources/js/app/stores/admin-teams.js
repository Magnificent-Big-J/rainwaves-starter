import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useAdminTeamsStore = defineStore('adminTeams', {
    state: () => ({
        rows: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        },
        loading: false,
        detail: null,
        detailLoading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 15, search = '', sortBy = '', sortDirection = 'asc' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });

                if (search) params.set('search', search);
                if (sortBy) {
                    params.set('sort_by', sortBy);
                    params.set('sort_direction', sortDirection);
                }

                const response = await v1(`admin/teams?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta?.pagination ?? this.meta;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async fetchDetail(teamId) {
            this.detailLoading = true;
            this.detail = null;

            try {
                const response = await v1(`admin/teams/${teamId}`);
                this.detail = response?.data ?? null;

                return this.detail;
            } finally {
                this.detailLoading = false;
            }
        },
    },
});
