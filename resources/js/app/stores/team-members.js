import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { useAppErrorsStore } from './app-errors';
import { normalizeErrorMessage } from './auth-shared';

export const useTeamMembersStore = defineStore('teamMembers', {
    state: () => ({
        rows: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        },
        invites: [],
        loading: false,
        invitesLoading: false,
    }),
    actions: {
        async fetchMembers(teamId, { page = 1, perPage = 10, search = '', sortBy = '', sortDirection = 'asc' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });

                if (search) params.set('search', search);
                if (sortBy) {
                    params.set('sort_by', sortBy);
                    params.set('sort_direction', sortDirection);
                }

                const response = await v1(`teams/${teamId}/members?${params}`);

                this.rows = response?.data ?? [];
                this.meta = response?.meta?.pagination ?? this.meta;

                return response;
            } finally {
                this.loading = false;
            }
        },
        async updateMemberRole(teamId, userId, role) {
            try {
                const response = await v1(`teams/${teamId}/members/${userId}`, { method: 'PATCH', body: { role } });

                return { ok: true, member: response?.data };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to update that member.') };
            }
        },
        async removeMember(teamId, userId) {
            try {
                await v1(`teams/${teamId}/members/${userId}`, { method: 'DELETE' });

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to remove that member.') };
            }
        },
        async fetchInvites(teamId) {
            this.invitesLoading = true;

            try {
                const response = await v1(`teams/${teamId}/invites`);
                this.invites = response?.data ?? [];
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to load pending invites.') });
            } finally {
                this.invitesLoading = false;
            }
        },
        async createInvite(teamId, payload) {
            try {
                const response = await v1(`teams/${teamId}/invites`, { method: 'POST', body: payload });

                return { ok: true, invite: response?.data };
            } catch (error) {
                return {
                    ok: false,
                    message: normalizeErrorMessage(error, 'Unable to send that invitation.'),
                };
            }
        },
        async revokeInvite(teamId, inviteId) {
            try {
                await v1(`teams/${teamId}/invites/${inviteId}`, { method: 'DELETE' });

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to revoke that invitation.') };
            }
        },
    },
});
