<template>
    <div class="profile-page">
        <div class="profile-header">
            <p class="profile-eyebrow">Account</p>
            <h1 class="profile-title">Profile &amp; security</h1>
        </div>

        <div class="profile-grid">
            <div class="profile-col">
                <AppSectionCard title="Your profile" subtitle="Update your name, email, and avatar.">
                    <FormStatusAlert :message="profileMsg" :type="profileMsgType" />
                    <v-form class="form-stack" @submit.prevent="saveProfile">
                        <MediaUploader
                            v-model="profileForm.avatarFile"
                            :current-url="session.user?.avatar_url"
                            :name="session.user?.name"
                            @remove="profileForm.removeAvatar = true"
                        />
                        <AppTextField
                            v-model="profileForm.name"
                            label="Full name"
                            :error-messages="profileErrors.name"
                            autocomplete="name"
                        />
                        <AppTextField
                            v-model="profileForm.email"
                            label="Email address"
                            type="email"
                            :error-messages="profileErrors.email"
                            autocomplete="email"
                        />
                        <FormActions submit-label="Save profile" :loading="profileStore.loading" />
                    </v-form>
                </AppSectionCard>

                <AppSectionCard title="Change password" subtitle="Requires your current password.">
                    <FormStatusAlert :message="passwordMsg" :type="passwordMsgType" />
                    <v-form class="form-stack" @submit.prevent="savePassword">
                        <AppTextField
                            v-model="passwordForm.current_password"
                            label="Current password"
                            type="password"
                            :error-messages="passwordErrors.current_password"
                            autocomplete="current-password"
                        />
                        <AppTextField
                            v-model="passwordForm.password"
                            label="New password"
                            type="password"
                            :error-messages="passwordErrors.password"
                            autocomplete="new-password"
                        />
                        <AppTextField
                            v-model="passwordForm.password_confirmation"
                            label="Confirm new password"
                            type="password"
                            autocomplete="new-password"
                        />
                        <FormActions submit-label="Change password" :loading="profileStore.loading" />
                    </v-form>
                </AppSectionCard>
            </div>

            <div class="profile-col">
                <AppSectionCard title="Identity" subtitle="Your current roles and permissions.">
                    <div class="identity-section">
                        <div class="identity-row">
                            <span class="identity-label">Roles</span>
                            <div class="badge-list">
                                <AppStatusBadge
                                    v-for="role in session.user?.roles"
                                    :key="role"
                                    :status="role"
                                    :label="role"
                                />
                                <span v-if="!session.user?.roles?.length" class="badge-none">No roles assigned</span>
                            </div>
                        </div>
                        <div class="identity-row">
                            <span class="identity-label">Permissions</span>
                            <div class="badge-list">
                                <span
                                    v-for="perm in session.user?.permissions?.slice(0, 10)"
                                    :key="perm"
                                    class="perm-badge"
                                >{{ perm }}</span>
                                <span
                                    v-if="(session.user?.permissions?.length ?? 0) > 10"
                                    class="badge-overflow"
                                >
                                    +{{ session.user.permissions.length - 10 }} more
                                </span>
                                <span v-if="!session.user?.permissions?.length" class="badge-none">None</span>
                            </div>
                        </div>
                    </div>
                </AppSectionCard>

                <TwoFactorSetupPanel
                    :enabled="Boolean(twoFactor.status?.enabled)"
                    :channel="twoFactor.status?.channel ?? null"
                    :loading="twoFactor.loading"
                    :secret="twoFactor.setup.secret"
                    :qr-code-data-url="twoFactor.setup.qrCodeDataUrl"
                    :code="totpCode"
                    :email-pending="twoFactor.setup.emailPending"
                    :email-code="emailCode"
                    :user-email="session.user?.email ?? ''"
                    :message="twoFaMsg"
                    :message-type="twoFaMsgType"
                    @start="startTotpSetup"
                    @email="sendEmailCode"
                    @disable="showDisableConfirm = true"
                    @verify="verifyTotp"
                    @cancel="cancelTotpSetup"
                    @verify-email="verifyEmailSetup"
                    @cancel-email="cancelEmailSetup"
                    @update:code="totpCode = $event"
                    @update:email-code="emailCode = $event"
                />

                <AppSectionCard
                    v-if="showDisableConfirm"
                    title="Disable two-factor"
                    subtitle="Confirm with your current password before disabling account protection."
                >
                    <div class="form-stack form-stack--tight">
                        <AppTextField
                            v-model="twoFaPassword"
                            label="Confirm your password"
                            type="password"
                            autocomplete="current-password"
                            hint="Required to disable two-factor authentication."
                            persistent-hint
                        />
                        <div class="twofa-confirm-actions">
                            <v-btn
                                color="error"
                                variant="tonal"
                                size="small"
                                :loading="twoFactor.loading"
                                @click="disableTwoFactor"
                            >
                                Confirm disable
                            </v-btn>
                            <v-btn variant="text" @click="cancelDisableTwoFactor">Cancel</v-btn>
                        </div>
                    </div>
                </AppSectionCard>

                <RecoveryCodesPanel
                    v-if="twoFactor.setup.recoveryCodes.length"
                    :codes="twoFactor.setup.recoveryCodes"
                    :loading="twoFactor.loading"
                    @regenerate="regenerateCodes"
                />
            </div>
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
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import AppTextField from '../../components/AppTextField.vue';
import FormActions from '../../components/FormActions.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import MediaUploader from '../../components/MediaUploader.vue';
import RecoveryCodesPanel from '../../components/RecoveryCodesPanel.vue';
import TwoFactorSetupPanel from '../../components/TwoFactorSetupPanel.vue';
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

const twoFaMsgType = ref('success');
const twoFaMsg = ref('');
const twoFaPassword = ref('');
const totpCode = ref('');
const emailCode = ref('');
const showDisableConfirm = ref(false);

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

const cancelTotpSetup = () => {
    twoFactor.setup.secret = null;
    totpCode.value = '';
};

const cancelEmailSetup = () => {
    twoFactor.setup.emailPending = false;
    emailCode.value = '';
};

const verifyEmailSetup = async () => {
    try {
        await twoFactor.verifyEmailSetup(emailCode.value);
        emailCode.value = '';
        setTwoFaMsg('success', 'Email OTP enabled. You will be asked for a code at each login.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Invalid code — try again.');
    }
};

const cancelDisableTwoFactor = () => {
    showDisableConfirm.value = false;
    twoFaPassword.value = '';
};

const startTotpSetup = async () => {
    try {
        await twoFactor.enableTotp('', session.user?.email || '');
        setTwoFaMsg('success', 'Scan the QR code then enter the 6-digit code to complete setup.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Unable to start authenticator setup.');
    }
};

const verifyTotp = async () => {
    try {
        await twoFactor.verifyTotp(totpCode.value);
        totpCode.value = '';
        await twoFactor.getStatus();
        setTwoFaMsg('success', 'Authenticator app enabled. Save your recovery codes.');
    } catch (error) {
        setTwoFaMsg('error', error?.data?.message || 'Invalid code — try again.');
    }
};

const sendEmailCode = async () => {
    twoFaMsg.value = '';
    try {
        await twoFactor.resendLoginCode('');
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
        twoFaPassword.value = '';
        showDisableConfirm.value = false;
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
    padding: 2.25rem 2rem 4rem;
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    gap: 2rem;
}

.profile-eyebrow {
    margin: 0 0 0.25rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: var(--rw-600);
}

.profile-title {
    margin: 0;
    font-size: clamp(1.8rem, 3.5vw, 2.75rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--rw-ink);
    line-height: 1;
}

.profile-grid {
    display: grid;
    gap: 1.25rem;
    align-items: start;
}

.profile-col {
    display: grid;
    gap: 1.25rem;
    align-items: start;
}

@media (min-width: 860px) {
    .profile-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.form-stack {
    display: grid;
    gap: 0.875rem;
}

.form-stack--tight {
    gap: 0.75rem;
}

.identity-section {
    display: grid;
    gap: 1.125rem;
}

.identity-row {
    display: grid;
    gap: 0.5rem;
}

.identity-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--rw-dim);
}

.badge-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.perm-badge {
    display: inline-flex;
    padding: 0.15rem 0.55rem;
    background: var(--rw-surface-2);
    color: var(--rw-muted);
    border: 1px solid var(--rw-border);
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 500;
    font-family: ui-monospace, monospace;
}

.badge-overflow {
    font-size: 0.75rem;
    color: var(--rw-dim);
    align-self: center;
}

.badge-none {
    font-size: 0.75rem;
    color: var(--rw-dim);
}

.twofa-confirm-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
</style>
