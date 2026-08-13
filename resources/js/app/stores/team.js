import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { useAppErrorsStore } from './app-errors';
import { normalizeErrorMessage } from './auth-shared';
import { useSessionStore } from './session';

export const useTeamStore = defineStore('team', {
    state: () => ({
        team: null,
        myRole: null,
        teams: [],
        loading: false,
        loaded: false,
    }),
    actions: {
        async fetch() {
            this.loading = true;

            try {
                const response = await v1('team');
                const data = response?.data ?? {};

                this.team = data.team ?? null;
                this.myRole = data.my_role ?? null;
                this.loaded = true;
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to load your team.') });
            } finally {
                this.loading = false;
            }
        },
        // Powers the sidebar switcher — every team the caller belongs to, not just the
        // active one `fetch()` returns.
        async fetchTeams() {
            try {
                const response = await v1('teams');
                this.teams = response?.data ?? [];
            } catch (error) {
                useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to load your teams.') });
            }
        },
        async create(name) {
            try {
                const response = await v1('teams', { method: 'POST', body: { name } });
                const team = response?.data ?? null;

                this.team = team;
                this.myRole = 'owner';
                await this.fetchTeams();

                return { ok: true, team };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to create that team.') };
            }
        },
        async rename(name) {
            if (!this.team) {
                return { ok: false, message: 'No active team.' };
            }

            try {
                const response = await v1(`teams/${this.team.id}`, { method: 'PATCH', body: { name } });
                this.team = response?.data ?? this.team;

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to rename that team.') };
            }
        },
        async switchTeam(teamId) {
            try {
                await v1(`teams/${teamId}/switch`, { method: 'POST' });
                await this.fetch();

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to switch teams.') };
            }
        },
        async acceptInvite(token) {
            try {
                await v1(`team-invites/${token}/accept`, { method: 'POST' });
                await this.fetch();

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to accept that invitation.') };
            }
        },
        // For an invitee with no account yet — bypasses the general registration gate
        // entirely (see TeamInviteController::registerAndAccept()), so this works even
        // when public self-registration is closed. Logs the new account straight in on
        // the backend; sync the session store here so the rest of the SPA immediately
        // reflects it too, not just this store's own team state.
        async registerAndAccept(token, payload) {
            try {
                await v1(`team-invites/${token}/register`, { method: 'POST', body: payload });
                await useSessionStore().fetchUser();
                await this.fetch();

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to create your account.') };
            }
        },
        async deleteTeam() {
            if (!this.team) {
                return { ok: false, message: 'No active team.' };
            }

            try {
                await v1(`teams/${this.team.id}`, { method: 'DELETE' });
                this.team = null;
                this.myRole = null;
                await this.fetchTeams();

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to delete that team.') };
            }
        },
    },
});
