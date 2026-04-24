<template>
    <div class="profile-page">
        <div class="page-wrap">
            <div class="page-header">
                <div>
                    <p class="page-eyebrow">Account</p>
                    <h1 class="page-title">Profile &amp; security</h1>
                </div>
            </div>

            <v-row dense>
                <v-col cols="12" md="5">
                    <AppSectionCard title="Your profile" subtitle="Update name, email, and avatar.">
                        <FormStatusAlert :message="profileMsg" :type="profileMsgType" />

                        <v-form class="form-stack" @submit.prevent="saveProfile">
                            <MediaUploader
                                v-model="profileForm.avatarFile"
                                :current-url="session.user?.avatar_url"
                                :name="session.user?.name"
                                @remove="profileForm.removeAvatar = true"
                            />

                            <v-text-field
                                v-model="profileForm.name"
                                label="Full name"
                                :error-messages="profileErrors.name"
                                autocomplete="name"
                            />

                            <v-text-field
                                v-model="profileForm.email"
                                label="Email address"
                                type="email"
                                :error-messages="profileErrors.email"
                                autocomplete="email"
                            />

                            <FormActions
                                submit-label="Save profile"
                                :loading="profileStore.loading"
                            />
                        </v-form>
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" md="7">
                    <AppSectionCard title="Identity" subtitle="Your current roles and permissions.">
                        <div class="identity-grid">
                            <div class="identity-row">
                                <span class="identity-label">Roles</span>
                                <div class="identity-chips">
                                    <v-chip
                                        v-for="role in session.user?.roles"
                                        :key="role"
                                        size="small"
                                        color="primary"
                                        variant="tonal"
                                    >
                                        {{ role }}
                                    </v-chip>
                                    <span v-if="!session.user?.roles?.length" class="text-muted">None</span>
                                </div>
                            </div>
                            <div class="identity-row">
                                <span class="identity-label">Permissions</span>
                                <div class="identity-chips">
                                    <v-chip
                                        v-for="perm in session.user?.permissions?.slice(0, 8)"
                                        :key="perm"
                                        size="x-small"
                                        variant="outlined"
                                    >
                                        {{ perm }}
                                    </v-chip>
                                    <span
                                        v-if="(session.user?.permissions?.length ?? 0) > 8"
                                        class="text-muted text-sm"
                                    >
                                        +{{ session.user.permissions.length - 8 }} more
                                    </span>
                                    <span v-if="!session.user?.permissions?.length" class="text-muted">None</span>
                                </div>
                            </div>
                        </div>
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" md="5">
                    <AppSectionCard title="Change password" subtitle="Requires your current password.">
                        <FormStatusAlert :message="passwordMsg" :type="passwordMsgType" />

                        <v-form class="form-stack" @submit.prevent="savePassword">
                            <v-text-field
                                v-model="passwordForm.current_password"
                                label="Current password"
                                type="password"
                                :error-messages="passwordErrors.current_password"
                                autocomplete="current-password"
                            />
                            <v-text-field
                                v-model="passwordForm.password"
                                label="New password"
                                type="password"
                                :error-messages="passwordErrors.password"
                                autocomplete="new-password"
                            />
                            <v-text-field
                                v-model="passwordForm.password_confirmation"
                                label="Confirm new password"
                                type="password"
                                autocomplete="new-password"
                            />

                            <FormActions
                                submit-label="Change password"
                                :loading="profileStore.loading"
                            />
                        </v-form>
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" md="7">
                    <AppSectionCard title="Two-factor authentication">
                        <div class="twofa-status">
                            <div class="twofa-badge" :class="twoFactor.status?.enabled ? 'twofa-badge--on' : 'twofa-badge--off'">
                                <v-icon size="16">{{ twoFactor.status?.enabled ? 'mdi-shield-check' : 'mdi-shield-off-outline' }}</v-icon>
                                {{ twoFactor.status?.enabled ? 'Enabled' : 'Disabled' }}
                            </div>
                            <span v-if="twoFactor.status?.channel" class="text-muted text-sm">via {{ twoFactor.status.channel }}</span>
                        </div>

                        <FormStatusAlert :message="twoFaMsg" :type="twoFaMsgType" />

                        <v-form class="form-stack" @submit.prevent="startTotpSetup">
                            <v-text-field
                                v-model="twoFaPassword"
                                label="Current password"
                                type="password"
                                autocomplete="current-password"
                                hint="Required to change 2FA settings."
                                persistent-hint
                            />

                            <div class="twofa-actions">
                                <v-btn
                                    type="submit"
                                    color="primary"
                                    variant="tonal"
                                    :loading="twoFactor.loading"
                                >
                                    Set up authenticator app
                                </v-btn>
                                <v-btn
                                    color="primary"
                                    variant="outlined"
                                    :loading="twoFactor.loading"
                                    @click="sendEmailCode"
                                >
                                    Send email OTP
                                </v-btn>
                                <v-btn
                                    v-if="twoFactor.status?.enabled"
                                    color="error"
                                    variant="text"
                                    :loading="twoFactor.loading"
                                    @click="disableTwoFactor"
                                >
                                    Disable 2FA
                                </v-btn>
                            </div>
                        </v-form>

                        <div v-if="twoFactor.setup.secret" class="totp-setup">
                            <p class="text-muted text-sm">Scan this QR with your authenticator app, then verify the code.</p>
                            <v-img
                                v-if="twoFactor.setup.qrCodeDataUrl"
                                :src="twoFactor.setup.qrCodeDataUrl"
                                max-width="200"
                                class="totp-qr"
                            />
                            <code class="totp-secret">{{ twoFactor.setup.secret }}</code>

                            <v-form class="form-stack" @submit.prevent="verifyTotp">
                                <v-text-field
                                    v-model="totpCode"
                                    label="Authenticator code"
                                    autocomplete="one-time-code"
                                />
                                <FormActions submit-label="Verify code" :loading="twoFactor.loading" />
                            </v-form>
                        </div>

                        <div v-if="twoFactor.setup.recoveryCodes.length" class="recovery-codes">
                            <p class="text-sm" style="font-weight:600;">Recovery codes</p>
                            <div class="recovery-codes__grid">
                                <code v-for="code in twoFactor.setup.recoveryCodes" :key="code" class="recovery-code">
                                    {{ code }}
                                </code>
                            </div>
                            <v-btn
                                size="small"
                                variant="text"
                                color="primary"
                                :loading="twoFactor.loading"
                                @click="regenerateCodes"
                            >
                                Regenerate recovery codes
                            </v-btn>
                        </div>
                    </AppSectionCard>
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
import { onMounted, reactive, ref } from 'vue';

import AppSectionCard from '../../components/AppSectionCard.vue';
import FormActions from '../../components/FormActions.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import MediaUploader from '../../components/MediaUploader.vue';
import { useProfileStore } from '../../stores/profile';
import { useSessionStore } from '../../stores/session';
import { useTwoFactorStore } from '../../stores/two-factor';

const session = useSessionStore();
const profileStore = useProfileStore();
const twoFactor = useTwoFactorStore();

const profileForm = reactive({ name: '', email: '', avatarFile: null, removeAvatar: false });
const profileErrors = ref({});
const profileMsg = ref('');
const profileMsgType = ref('success');

const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' });
const passwordErrors = ref({});
const passwordMsg = ref('');
const passwordMsgType = ref('success');

const twoFaPassword = ref('');
const twoFaMsgType = ref('success');
const twoFaMsg = ref('');
const totpCode = ref('');

const syncProfileForm = () => {
    profileForm.name = session.user?.name || '';
    profileForm.email = session.user?.email || '';
};

const saveProfile = async () => {
    profileErrors.value = {};
    profileMsg.value = '';

    try {
        await profileStore.updateProfile({
            name: profileForm.name,
            email: profileForm.email,
            avatar: profileForm.avatarFile,
            remove_avatar: profileForm.removeAvatar,
        });

        profileForm.avatarFile = null;
        profileForm.removeAvatar = false;
        profileMsg.value = 'Profile updated.';
        profileMsgType.value = 'success';
    } catch (error) {
        profileErrors.value = error?.data?.errors ?? {};
        profileMsg.value = error?.data?.message || 'Unable to update profile.';
        profileMsgType.value = 'error';
    }
};

const savePassword = async () => {
    passwordErrors.value = {};
    passwordMsg.value = '';

    try {
        await profileStore.updatePassword(passwordForm);
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        passwordMsg.value = 'Password changed.';
        passwordMsgType.value = 'success';
    } catch (error) {
        passwordErrors.value = error?.data?.errors ?? {};
        passwordMsg.value = error?.data?.message || 'Unable to change password.';
        passwordMsgType.value = 'error';
    }
};

const setTwoFaMsg = (type, msg) => {
    twoFaMsgType.value = type;
    twoFaMsg.value = msg;
};

const startTotpSetup = async () => {
    try {
        await twoFactor.enableTotp(twoFaPassword.value, session.user?.email || '');
        setTwoFaMsg('success', 'Scan the QR code then verify the code to complete setup.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Unable to start authenticator setup.');
    }
};

const verifyTotp = async () => {
    try {
        await twoFactor.verifyTotp(totpCode.value);
        totpCode.value = '';
        await twoFactor.getStatus();
        setTwoFaMsg('success', 'Authenticator app enabled.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Invalid code.');
    }
};

const sendEmailCode = async () => {
    try {
        await twoFactor.resendLoginCode(twoFaPassword.value);
        setTwoFaMsg('success', 'Verification code sent to your email.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Unable to send code.');
    }
};

const regenerateCodes = async () => {
    try {
        await twoFactor.regenerateRecoveryCodes(twoFaPassword.value);
        setTwoFaMsg('success', 'Recovery codes regenerated.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Unable to regenerate codes.');
    }
};

const disableTwoFactor = async () => {
    try {
        await twoFactor.disable(twoFaPassword.value);
        await twoFactor.getStatus();
        setTwoFaMsg('success', 'Two-factor authentication disabled.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Unable to disable 2FA.');
    }
};

onMounted(async () => {
    syncProfileForm();
    await twoFactor.getStatus().catch(() => null);
});
</script>

<style scoped>
.profile-page {
    padding: 2rem 1.25rem 4rem;
}

.page-wrap {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.page-eyebrow {
    margin: 0 0 0.3rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: var(--starter-accent);
    font-size: 0.8rem;
    font-weight: 700;
}

.page-title {
    margin: 0;
    font-size: clamp(1.8rem, 3.5vw, 2.8rem);
}

.form-stack {
    display: grid;
    gap: 0.75rem;
}

.identity-grid {
    display: grid;
    gap: 1rem;
}

.identity-row {
    display: grid;
    gap: 0.5rem;
}

.identity-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--starter-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.75rem;
}

.identity-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.twofa-status {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.twofa-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
}

.twofa-badge--on {
    background: rgba(21, 115, 71, 0.12);
    color: #157347;
}

.twofa-badge--off {
    background: rgba(17, 34, 51, 0.06);
    color: var(--starter-muted);
}

.twofa-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.totp-setup {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(17, 34, 51, 0.08);
    display: grid;
    gap: 0.75rem;
}

.totp-qr {
    border-radius: 0.75rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
    overflow: hidden;
}

.totp-secret {
    display: inline-block;
    padding: 0.6rem 1rem;
    background: rgba(17, 34, 51, 0.05);
    border-radius: 0.5rem;
    font-size: 0.9rem;
    letter-spacing: 0.05em;
}

.recovery-codes {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(17, 34, 51, 0.08);
    display: grid;
    gap: 0.75rem;
}

.recovery-codes__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 0.5rem;
}

.recovery-code {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.6rem;
    background: rgba(17, 34, 51, 0.05);
    border: 1px solid rgba(17, 34, 51, 0.08);
    font-size: 0.85rem;
}

.text-muted {
    color: var(--starter-muted);
}

.text-sm {
    font-size: 0.875rem;
}
</style>
