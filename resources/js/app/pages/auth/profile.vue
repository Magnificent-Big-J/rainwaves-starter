<template>
    <div class="profile-page">
        <div class="profile-page__wrap">
            <div class="profile-page__header">
                <div>
                    <p class="profile-page__eyebrow">Account</p>
                    <h1 class="profile-page__title">Profile and security</h1>
                </div>
                <v-btn color="primary" @click="refresh">Refresh 2FA status</v-btn>
            </div>

            <v-row dense>
                <v-col cols="12" md="7">
                    <v-card class="profile-card">
                        <v-card-title>Signed-in user</v-card-title>
                        <v-card-text>
                            <div class="profile-list">
                                <div>
                                    <strong>Name</strong>
                                    <div>{{ session.user?.name || 'Unknown' }}</div>
                                </div>
                                <div>
                                    <strong>Email</strong>
                                    <div>{{ session.user?.email || 'Unknown' }}</div>
                                </div>
                                <div>
                                    <strong>Roles</strong>
                                    <div>{{ session.user?.roles?.join(', ') || 'None' }}</div>
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" md="5">
                    <v-card class="profile-card">
                        <v-card-title>Two-factor status</v-card-title>
                        <v-card-text class="profile-list">
                            <div>
                                <strong>Enabled</strong>
                                <div>{{ twoFactor.status?.enabled ? 'Yes' : 'No' }}</div>
                            </div>
                            <div>
                                <strong>Channel</strong>
                                <div>{{ twoFactor.status?.channel || 'Unknown' }}</div>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </div>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Profile",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { onMounted } from 'vue';

import { useSessionStore } from '../../stores/session';
import { useTwoFactorStore } from '../../stores/two-factor';

const session = useSessionStore();
const twoFactor = useTwoFactorStore();

const refresh = async () => {
    await session.ensureLoaded();
    await twoFactor.getStatus().catch(() => null);
};

onMounted(refresh);
</script>

<style scoped>
.profile-page {
    padding: 2rem 1.25rem 4rem;
}

.profile-page__wrap {
    max-width: 1180px;
    margin: 0 auto;
}

.profile-page__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.profile-page__eyebrow {
    margin: 0;
    color: var(--starter-accent);
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.8rem;
    font-weight: 700;
}

.profile-page__title {
    margin: 0.65rem 0 0;
    font-size: clamp(2rem, 4vw, 3rem);
}

.profile-card {
    height: 100%;
    background: rgba(255, 253, 248, 0.9);
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.profile-list {
    display: grid;
    gap: 1rem;
}
</style>
