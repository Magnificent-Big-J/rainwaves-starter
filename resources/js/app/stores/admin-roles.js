import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { useAppErrorsStore } from './app-errors';
import { normalizeErrorMessage, validationErrors } from './auth-shared';

export const useAdminRolesStore = defineStore('adminRoles', {
    state: () => ({
        rows: [],
        options: { permissions: [] },
        loading: false,
        loaded: false,
    }),
    actions: {
        async fetch() {
            this.loading = true;

            try {
                const response = await v1('roles');
                this.rows = response?.data ?? [];
                this.options = response?.meta?.options ?? { permissions: [] };
                this.loaded = true;
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to load roles.') });
            } finally {
                this.loading = false;
            }
        },
        async updatePermissions(roleId, permissions) {
            try {
                const response = await v1(`roles/${roleId}/permissions`, {
                    method: 'PUT',
                    body: { permissions },
                });

                const updated = response?.data;
                this.rows = this.rows.map((role) => (role.id === roleId ? updated : role));

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to update role permissions.'), errors: validationErrors(error) };
            }
        },
    },
});
