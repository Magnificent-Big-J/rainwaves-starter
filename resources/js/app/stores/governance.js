import { defineStore } from 'pinia';

import { v1 } from '../utils/api';
import { normalizeErrorMessage } from './auth-shared';

export const useGovernanceStore = defineStore('governance', {
    state: () => ({ deleting: false }),
    actions: {
        async deleteAccount() {
            this.deleting = true;

            try {
                await v1('governance/account', { method: 'DELETE' });

                return { ok: true };
            } catch (error) {
                return { ok: false, message: normalizeErrorMessage(error, 'Unable to delete your account.') };
            } finally {
                this.deleting = false;
            }
        },
    },
});
