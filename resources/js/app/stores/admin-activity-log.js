import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { useAppErrorsStore } from './app-errors';
import { normalizeErrorMessage } from './auth-shared';

export const useAdminActivityLogStore = defineStore('adminActivityLog', {
    state: () => ({
        rows: [],
        options: { log_names: [] },
        pagination: null,
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, logName = '', search = '' } = {}) {
            this.loading = true;

            try {
                const response = await v1('activity-log', {
                    params: { page, log_name: logName || undefined, search: search || undefined },
                });

                this.rows = response?.data ?? [];
                this.options = response?.meta?.options ?? { log_names: [] };
                this.pagination = response?.meta?.pagination ?? null;
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to load the audit log.') });
            } finally {
                this.loading = false;
            }
        },
    },
});
