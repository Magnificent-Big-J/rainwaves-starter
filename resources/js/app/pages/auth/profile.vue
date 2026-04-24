<template>
    <div class="profile-page">

        <div class="profile-header">
            <p class="profile-eyebrow">Account</p>
            <h1 class="profile-title">Profile &amp; security</h1>
        </div>

        <div class="profile-grid">

            <!-- ── Left column ─────────────────────────────── -->
            <div class="profile-col">

                <!-- Profile -->
                <AppSectionCard title="Your profile" subtitle="Update your name, email, and avatar.">
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
                        <FormActions submit-label="Save profile" :loading="profileStore.loading" />
                    </v-form>
                </AppSectionCard>

                <!-- Password -->
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
                        <FormActions submit-label="Change password" :loading="profileStore.loading" />
                    </v-form>
                </AppSectionCard>

            </div>

            <!-- ── Right column ────────────────────────────── -->
            <div class="profile-col">

                <!-- Identity -->
                <AppSectionCard title="Identity" subtitle="Your current roles and permissions.">
                    <div class="identity-section">
                        <div class="identity-row">
                            <span class="identity-label">Roles</span>
                            <div class="badge-list">
                                <span
                                    v-for="role in session.user?.roles"
                                    :key="role"
                                    class="role-badge"
                                >{{ role }}</span>
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

                <!-- Two-factor -->
                <AppSectionCard title="Two-factor authentication">
                    <template #header>
                        <div
                            class="twofa-status-badge"
                            :class="twoFactor.status?.enabled ? 'twofa-status-badge--on' : 'twofa-status-badge--off'"
                        >
                            <v-icon size="13">{{ twoFactor.status?.enabled ? 'mdi-shield-check' : 'mdi-shield-off-outline' }}</v-icon>
                            {{ twoFactor.status?.enabled ? 'Enabled' : 'Disabled' }}
                        </div>
                    </template>

                    <FormStatusAlert :message="twoFaMsg" :type="twoFaMsgType" />

                    <!-- Setup not in progress -->
                    <template v-if="!twoFactor.setup.secret">
                        <div class="twofa-actions">
                            <button class="twofa-btn" :disabled="twoFactor.loading" @click="startTotpSetup">
                                <v-icon size="17" color="var(--rw-600)">mdi-qrcode</v-icon>
                                Set up authenticator app
                            </button>
                            <button class="twofa-btn" :disabled="twoFactor.loading" @click="sendEmailCode">
                                <v-icon size="17" color="var(--rw-600)">mdi-email-outline</v-icon>
                                Send email OTP
                            </button>
                        </div>

                        <!-- Disable confirmation — shown only when user requests it -->
                        <template v-if="twoFactor.status?.enabled">
                            <div v-if="!showDisableConfirm" class="twofa-disable-row">
                                <button
                                    class="twofa-btn twofa-btn--danger"
                                    :disabled="twoFactor.loading"
                                    @click="showDisableConfirm = true"
                                >
                                    <v-icon size="17">mdi-shield-remove-outline</v-icon>
                                    Disable 2FA
                                </button>
                            </div>
                            <div v-else class="twofa-disable-confirm">
                                <v-text-field
                                    v-model="twoFaPassword"
                                    label="Confirm your password"
                                    type="password"
                                    autocomplete="current-password"
                                    hint="Required to disable two-factor authentication."
                                    persistent-hint
                                    autofocus
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
                                    <button
                                        type="button"
                                        class="totp-cancel"
                                        @click="showDisableConfirm = false; twoFaPassword = ''"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </template>
                    </template>

                    <!-- TOTP setup in progress -->
                    <template v-if="twoFactor.setup.secret">
                        <div class="totp-setup">
                            <div class="totp-setup__qr-row">
                                <img
                                    v-if="twoFactor.setup.qrCodeDataUrl"
                                    :src="twoFactor.setup.qrCodeDataUrl"
                                    class="totp-qr"
                                    alt="QR code"
                                />
                                <div class="totp-setup__instructions">
                                    <p class="totp-setup__step">
                                        <span class="totp-step-num">1</span>
                                        Open your authenticator app and scan the QR code.
                                    </p>
                                    <p class="totp-setup__step">
                                        <span class="totp-step-num">2</span>
                                        Or enter this key manually:
                                    </p>
                                    <code class="totp-secret">{{ twoFactor.setup.secret }}</code>
                                    <p class="totp-setup__step">
                                        <span class="totp-step-num">3</span>
                                        Enter the 6-digit code to verify.
                                    </p>
                                </div>
                            </div>

                            <v-form class="form-stack form-stack--tight" @submit.prevent="verifyTotp">
                                <v-text-field
                                    v-model="totpCode"
                                    label="6-digit code"
                                    autocomplete="one-time-code"
                                    inputmode="numeric"
                                    maxlength="6"
                                />
                                <div class="totp-verify-row">
                                    <v-btn
                                        type="submit"
                                        color="primary"
                                        :loading="twoFactor.loading"
                                    >
                                        Verify and enable
                                    </v-btn>
                                    <button
                                        type="button"
                                        class="totp-cancel"
                                        @click="twoFactor.setup.secret = null"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </v-form>
                        </div>
                    </template>

                    <!-- Recovery codes -->
                    <div v-if="twoFactor.setup.recoveryCodes.length" class="recovery-section">
                        <div class="recovery-section__head">
                            <div>
                                <p class="recovery-section__title">Recovery codes</p>
                                <p class="recovery-section__sub">Store these somewhere safe — each can only be used once.</p>
                            </div>
                            <button
                                class="twofa-btn twofa-btn--sm"
                                :disabled="twoFactor.loading"
                                @click="regenerateCodes"
                            >
                                <v-icon size="14">mdi-refresh</v-icon>
                                Regenerate
                            </button>
                        </div>
                        <div class="recovery-grid">
                            <code
                                v-for="code in twoFactor.setup.recoveryCodes"
                                :key="code"
                                class="recovery-code"
                            >{{ code }}</code>
                        </div>
                    </div>

                </AppSectionCard>

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

const twoFaMsgType = ref('success');
const twoFaMsg = ref('');
const twoFaPassword = ref('');
const totpCode = ref('');
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
    try {
        await twoFactor.resendLoginCode('');
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
/* ── Page shell ────────────────────────────────────── */
.profile-page {
    padding: 2.25rem 2rem 4rem;
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    gap: 2rem;
}

/* ── Header ────────────────────────────────────────── */
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

/* ── Grid ──────────────────────────────────────────── */
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

/* ── Forms ─────────────────────────────────────────── */
.form-stack {
    display: grid;
    gap: 0.875rem;
}

.form-stack--tight {
    gap: 0.75rem;
}

/* ── Identity ──────────────────────────────────────── */
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

.role-badge {
    display: inline-flex;
    padding: 0.2rem 0.65rem;
    background: var(--rw-50);
    color: var(--rw-700);
    border: 1px solid var(--rw-100);
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
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
    font-size: 0.8rem;
    color: var(--rw-dim);
}

/* ── 2FA status badge (in card header) ────────────── */
.twofa-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    margin-top: 0.4rem;
}

.twofa-status-badge--on {
    background: rgba(21, 128, 61, 0.1);
    color: #15803d;
    border: 1px solid rgba(21, 128, 61, 0.2);
}

.twofa-status-badge--off {
    background: var(--rw-surface-2);
    color: var(--rw-muted);
    border: 1px solid var(--rw-border);
}

/* ── 2FA action buttons ────────────────────────────── */
.twofa-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.twofa-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1rem;
    background: var(--rw-surface-2);
    border: 1px solid var(--rw-border);
    border-radius: 0.625rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--rw-ink-2);
    cursor: pointer;
    text-align: left;
    transition: background 0.12s, border-color 0.12s;
    width: 100%;
}

.twofa-btn:hover:not(:disabled) {
    background: var(--rw-border);
    border-color: var(--rw-100);
}

.twofa-btn:disabled {
    opacity: 0.5;
    cursor: default;
}

.twofa-btn--danger {
    color: #b91c1c;
    border-color: rgba(185, 28, 28, 0.2);
    background: rgba(185, 28, 28, 0.04);
}

.twofa-btn--danger:hover:not(:disabled) {
    background: rgba(185, 28, 28, 0.08);
    border-color: rgba(185, 28, 28, 0.3);
}

.twofa-disable-row {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--rw-border);
}

.twofa-disable-confirm {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--rw-border);
    display: grid;
    gap: 0.75rem;
}

.twofa-confirm-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.twofa-btn--sm {
    width: auto;
    padding: 0.35rem 0.75rem;
    font-size: 0.8rem;
    flex-shrink: 0;
}

/* ── TOTP setup ────────────────────────────────────── */
.totp-setup {
    display: grid;
    gap: 1.25rem;
    padding-top: 0.25rem;
}

.totp-setup__qr-row {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
    flex-wrap: wrap;
}

.totp-qr {
    width: 140px;
    height: 140px;
    border-radius: 0.625rem;
    border: 1px solid var(--rw-border);
    flex-shrink: 0;
}

.totp-setup__instructions {
    display: grid;
    gap: 0.6rem;
    flex: 1;
    min-width: 160px;
}

.totp-setup__step {
    margin: 0;
    font-size: 0.825rem;
    color: var(--rw-muted);
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    line-height: 1.5;
}

.totp-step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 50%;
    background: var(--rw-50);
    border: 1px solid var(--rw-100);
    color: var(--rw-700);
    font-size: 0.65rem;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 0.1rem;
}

.totp-secret {
    display: block;
    padding: 0.45rem 0.75rem;
    background: var(--rw-surface-2);
    border: 1px solid var(--rw-border);
    border-radius: 0.5rem;
    font-size: 0.8rem;
    letter-spacing: 0.08em;
    color: var(--rw-ink-2);
    word-break: break-all;
}

.totp-verify-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.totp-cancel {
    background: none;
    border: none;
    font-size: 0.875rem;
    color: var(--rw-muted);
    cursor: pointer;
    padding: 0;
    transition: color 0.12s;
}

.totp-cancel:hover {
    color: var(--rw-ink);
}

/* ── Recovery codes ────────────────────────────────── */
.recovery-section {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid var(--rw-border);
    display: grid;
    gap: 0.875rem;
}

.recovery-section__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.recovery-section__title {
    margin: 0 0 0.2rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--rw-ink);
}

.recovery-section__sub {
    margin: 0;
    font-size: 0.78rem;
    color: var(--rw-muted);
}

.recovery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 0.5rem;
}

.recovery-code {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.55rem 0.75rem;
    background: var(--rw-surface-2);
    border: 1px solid var(--rw-border);
    border-radius: 0.5rem;
    font-size: 0.82rem;
    letter-spacing: 0.04em;
    color: var(--rw-ink-2);
    text-align: center;
}
</style>
