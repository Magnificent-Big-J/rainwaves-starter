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
                <v-col cols="12" md="6">
                    <v-card class="profile-card">
                        <v-card-title>Signed-in user</v-card-title>
                        <v-card-text>
                            <div class="profile-identity">
                                <v-avatar size="72" color="primary" variant="tonal">
                                    <v-img
                                        v-if="session.user?.avatar_url"
                                        :src="session.user.avatar_url"
                                        :alt="session.user?.name || 'Avatar'"
                                    />
                                    <span v-else>{{ initials }}</span>
                                </v-avatar>

                                <div>
                                    <strong>Name</strong>
                                    <div>{{ session.user?.name || 'Unknown' }}</div>
                                </div>
                            </div>

                            <div class="profile-list">
                                <div>
                                    <strong>Email</strong>
                                    <div>{{ session.user?.email || 'Unknown' }}</div>
                                </div>
                                <div>
                                    <strong>Roles</strong>
                                    <div>{{ session.user?.roles?.join(', ') || 'None' }}</div>
                                </div>
                                <div>
                                    <strong>Permissions</strong>
                                    <div>{{ session.user?.permissions?.join(', ') || 'None' }}</div>
                                </div>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" md="6">
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
                            <div>
                                <strong>Recovery codes left</strong>
                                <div>{{ twoFactor.status?.recovery_codes_remaining ?? 0 }}</div>
                            </div>
                            <div>
                                <strong>Verified</strong>
                                <div>{{ twoFactor.status?.verified ? 'Yes' : 'No' }}</div>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" md="7">
                    <v-card class="profile-card">
                        <v-card-title>Authenticator app</v-card-title>
                        <v-card-text class="profile-stack">
                            <FormStatusAlert :message="message" :type="messageType" />

                            <v-form class="profile-stack" @submit.prevent="startTotpSetup">
                                <v-text-field
                                    v-model="password"
                                    label="Current password"
                                    type="password"
                                    autocomplete="current-password"
                                    hint="Required before changing 2FA settings."
                                    persistent-hint
                                />

                                <FormActions
                                    submit-label="Generate authenticator secret"
                                    :loading="twoFactor.loading"
                                />
                            </v-form>

                            <div v-if="twoFactor.setup.secret" class="profile-stack">
                                <div>
                                    <strong>Secret</strong>
                                    <div class="profile-code">{{ twoFactor.setup.secret }}</div>
                                </div>

                                <v-img
                                    v-if="twoFactor.setup.qrCodeDataUrl"
                                    class="profile-qr"
                                    :src="twoFactor.setup.qrCodeDataUrl"
                                    max-width="220"
                                />

                                <v-form class="profile-stack" @submit.prevent="verifyTotp">
                                    <v-text-field
                                        v-model="totpCode"
                                        label="Authenticator code"
                                        autocomplete="one-time-code"
                                    />

                                    <FormActions
                                        submit-label="Verify authenticator"
                                        :loading="twoFactor.loading"
                                    />
                                </v-form>
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col cols="12" md="5">
                    <v-card class="profile-card">
                        <v-card-title>Recovery and email OTP</v-card-title>
                        <v-card-text class="profile-stack">
                            <v-btn
                                color="primary"
                                variant="tonal"
                                :loading="twoFactor.loading"
                                @click="sendEmailCode"
                            >
                                Send email verification code
                            </v-btn>

                            <v-btn
                                color="primary"
                                variant="outlined"
                                :loading="twoFactor.loading"
                                @click="regenerateCodes"
                            >
                                Regenerate recovery codes
                            </v-btn>

                            <v-btn
                                color="error"
                                variant="outlined"
                                :loading="twoFactor.loading"
                                @click="disableTwoFactor"
                            >
                                Disable two-factor
                            </v-btn>

                            <div v-if="twoFactor.setup.recoveryCodes.length" class="profile-stack">
                                <strong>Recovery codes</strong>
                                <div class="profile-code-grid">
                                    <code
                                        v-for="code in twoFactor.setup.recoveryCodes"
                                        :key="code"
                                        class="profile-code"
                                    >
                                        {{ code }}
                                    </code>
                                </div>
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
import { computed, onMounted, ref } from 'vue';

import { useSessionStore } from '../../stores/session';
import { useTwoFactorStore } from '../../stores/two-factor';

const session = useSessionStore();
const twoFactor = useTwoFactorStore();
const password = ref('');
const totpCode = ref('');
const message = ref('');
const messageType = ref('success');

const initials = computed(() => {
    const name = session.user?.name || 'RS';

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('');
});

const refresh = async () => {
    await session.ensureLoaded();
    await twoFactor.getStatus().catch(() => null);
};

const showMessage = (type, value) => {
    messageType.value = type;
    message.value = value;
};

const startTotpSetup = async () => {
    try {
        await twoFactor.enableTotp(password.value, session.user?.email || '');
        showMessage('success', 'Authenticator setup secret generated. Verify the code to finish enabling TOTP.');
    } catch (error) {
        showMessage('error', error?.data?.message || 'Unable to start authenticator setup.');
    }
};

const verifyTotp = async () => {
    try {
        await twoFactor.verifyTotp(totpCode.value);
        totpCode.value = '';
        await refresh();
        showMessage('success', 'Authenticator app enabled successfully.');
    } catch (error) {
        showMessage('error', error?.data?.message || 'Unable to verify the authenticator code.');
    }
};

const sendEmailCode = async () => {
    try {
        await twoFactor.resendLoginCode(password.value);
        showMessage('success', 'A verification code has been sent to the signed-in user email address.');
    } catch (error) {
        showMessage('error', error?.data?.message || 'Unable to send the email verification code.');
    }
};

const regenerateCodes = async () => {
    try {
        await twoFactor.regenerateRecoveryCodes(password.value);
        await refresh();
        showMessage('success', 'Recovery codes regenerated.');
    } catch (error) {
        showMessage('error', error?.data?.message || 'Unable to regenerate recovery codes.');
    }
};

const disableTwoFactor = async () => {
    try {
        await twoFactor.disable(password.value);
        await refresh();
        showMessage('success', 'Two-factor authentication disabled.');
    } catch (error) {
        showMessage('error', error?.data?.message || 'Unable to disable two-factor authentication.');
    }
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

.profile-identity {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.profile-list {
    display: grid;
    gap: 1rem;
}

.profile-stack {
    display: grid;
    gap: 1rem;
}

.profile-code-grid {
    display: grid;
    gap: 0.75rem;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
}

.profile-code {
    display: inline-flex;
    align-items: center;
    min-height: 2.75rem;
    padding: 0.75rem 0.9rem;
    border-radius: 0.9rem;
    background: rgba(17, 34, 51, 0.06);
    border: 1px solid rgba(17, 34, 51, 0.08);
    font-size: 0.9rem;
}

.profile-qr {
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid rgba(17, 34, 51, 0.08);
}
</style>
