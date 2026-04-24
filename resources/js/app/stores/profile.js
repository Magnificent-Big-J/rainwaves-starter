import { defineStore } from 'pinia';

import { api } from '../utils/api';
import { useSessionStore } from './session';

export const useProfileStore = defineStore('profileAuth', {
    state: () => ({
        profile: null,
        loading: false,
    }),
    actions: {
        async fetchProfile() {
            this.loading = true;

            try {
                const response = await api('/api/v1/profile');

                this.profile = response?.data ?? response;

                return this.profile;
            } finally {
                this.loading = false;
            }
        },

        async updateProfile(payload) {
            this.loading = true;

            try {
                const hasAvatarFile = payload?.avatar instanceof File;
                const shouldRemoveAvatar = Boolean(payload?.remove_avatar);

                let requestBody;

                if (hasAvatarFile || shouldRemoveAvatar) {
                    const formData = new FormData();

                    formData.append('name', payload.name || '');
                    formData.append('email', payload.email || '');

                    if (hasAvatarFile) {
                        formData.append('avatar', payload.avatar);
                    }

                    if (shouldRemoveAvatar) {
                        formData.append('remove_avatar', '1');
                    }

                    requestBody = formData;
                } else {
                    requestBody = { name: payload.name, email: payload.email };
                }

                const response = await api('/api/v1/profile', {
                    method: 'PATCH',
                    body: requestBody,
                });

                const profile = response?.data ?? response;

                this.profile = profile;

                const sessionStore = useSessionStore();

                sessionStore.setUser({
                    ...(sessionStore.user || {}),
                    name: profile?.name ?? sessionStore.user?.name,
                    email: profile?.email ?? sessionStore.user?.email,
                    avatar_url: profile?.avatar_url ?? sessionStore.user?.avatar_url,
                });

                return profile;
            } finally {
                this.loading = false;
            }
        },

        async updatePassword(payload) {
            this.loading = true;

            try {
                return await api('/api/v1/profile/password', {
                    method: 'PUT',
                    body: payload,
                });
            } finally {
                this.loading = false;
            }
        },
    },
});
