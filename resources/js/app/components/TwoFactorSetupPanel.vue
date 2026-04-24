<template>
    <AppSectionCard title="Two-factor authentication" subtitle="Protect your account with a second verification step at login.">
        <template #header>
            <AppStatusBadge
                :status="statusKey"
                :label="statusLabel"
                :icon="statusIcon"
            />
        </template>

        <FormStatusAlert :message="message" :type="messageType" />

        <!-- ── Idle: nothing in progress ───────────────────── -->
        <template v-if="!secret && !emailPending">

            <!-- Channel detail when enabled -->
            <div v-if="enabled && channel" class="twofa-channel">
                <v-icon size="16" color="var(--rw-600)">{{ channelIcon }}</v-icon>
                <span>Active method: <strong>{{ channelLabel }}</strong></span>
            </div>

            <div class="twofa-panel__actions">
                <button class="twofa-action-btn" :disabled="loading" @click="$emit('start')">
                    <span class="twofa-action-btn__icon">
                        <v-icon size="17" color="var(--rw-600)">mdi-qrcode</v-icon>
                    </span>
                    <span class="twofa-action-btn__body">
                        <strong>Authenticator app</strong>
                        <span>Scan a QR code with Google Authenticator or similar</span>
                    </span>
                </button>
                <button class="twofa-action-btn" :disabled="loading" @click="$emit('email')">
                    <span class="twofa-action-btn__icon">
                        <v-icon size="17" color="var(--rw-600)">mdi-email-outline</v-icon>
                    </span>
                    <span class="twofa-action-btn__body">
                        <strong>Email OTP</strong>
                        <span>Receive a one-time code to your email address at login</span>
                    </span>
                </button>
            </div>

            <div v-if="enabled" class="twofa-disable-row">
                <button class="twofa-text-btn twofa-text-btn--danger" :disabled="loading" @click="$emit('disable')">
                    <v-icon size="15">mdi-shield-remove-outline</v-icon>
                    Disable two-factor authentication
                </button>
            </div>
        </template>

        <!-- ── TOTP setup in progress ──────────────────────── -->
        <template v-if="secret">
            <div class="twofa-panel__setup">
                <img
                    v-if="qrCodeDataUrl"
                    :src="qrCodeDataUrl"
                    class="twofa-panel__qr"
                    alt="QR code"
                />
                <div class="twofa-panel__copy">
                    <p class="twofa-step">
                        <span class="twofa-step__num">1</span>
                        Open your authenticator app and scan the QR code, or enter the secret manually.
                    </p>
                    <code class="twofa-panel__secret">{{ secret }}</code>
                    <p class="twofa-step">
                        <span class="twofa-step__num">2</span>
                        Enter the 6-digit code to verify and activate.
                    </p>
                </div>
            </div>

            <v-form class="twofa-panel__verify" @submit.prevent="$emit('verify')">
                <AppTextField
                    :model-value="code"
                    label="6-digit code"
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    maxlength="6"
                    @update:model-value="$emit('update:code', $event)"
                />
                <div class="twofa-panel__verify-actions">
                    <v-btn type="submit" color="primary" :loading="loading">Verify and enable</v-btn>
                    <button type="button" class="twofa-text-btn" @click="$emit('cancel')">Cancel</button>
                </div>
            </v-form>
        </template>

        <!-- ── Email OTP setup in progress ────────────────── -->
        <template v-if="emailPending">
            <div class="twofa-email-setup">
                <div class="twofa-email-setup__notice">
                    <v-icon size="22" color="var(--rw-600)">mdi-email-fast-outline</v-icon>
                    <div>
                        <p class="twofa-email-setup__heading">Check your email</p>
                        <p class="twofa-email-setup__sub">
                            A verification code was sent to <strong>{{ userEmail }}</strong>.
                            Enter it below to activate email two-factor authentication.
                        </p>
                    </div>
                </div>
            </div>

            <v-form class="twofa-panel__verify" @submit.prevent="$emit('verify-email')">
                <AppTextField
                    :model-value="emailCode"
                    label="Verification code"
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    maxlength="6"
                    @update:model-value="$emit('update:email-code', $event)"
                />
                <div class="twofa-panel__verify-actions">
                    <v-btn type="submit" color="primary" :loading="loading">Verify and enable</v-btn>
                    <button type="button" class="twofa-text-btn" @click="$emit('cancel-email')">Cancel</button>
                </div>
            </v-form>
        </template>

    </AppSectionCard>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    enabled:        { type: Boolean, default: false },
    channel:        { type: String,  default: null },
    loading:        { type: Boolean, default: false },
    secret:         { type: String,  default: null },
    qrCodeDataUrl:  { type: String,  default: null },
    code:           { type: String,  default: '' },
    emailPending:   { type: Boolean, default: false },
    emailCode:      { type: String,  default: '' },
    userEmail:      { type: String,  default: '' },
    message:        { type: String,  default: '' },
    messageType:    { type: String,  default: 'success' },
});

defineEmits(['start', 'email', 'disable', 'verify', 'cancel', 'verify-email', 'cancel-email', 'update:code', 'update:email-code']);

const statusKey = computed(() => {
    if (!props.enabled) return 'inactive';
    return props.channel === 'email' ? 'info' : 'active';
});

const statusLabel = computed(() => {
    if (!props.enabled) return 'Disabled';
    if (props.channel === 'totp') return 'Enabled · TOTP';
    if (props.channel === 'email') return 'Enabled · Email';
    return 'Enabled';
});

const statusIcon = computed(() => {
    if (!props.enabled) return 'mdi-shield-off-outline';
    return 'mdi-shield-check';
});

const channelIcon = computed(() =>
    props.channel === 'email' ? 'mdi-email-outline' : 'mdi-cellphone-key'
);

const channelLabel = computed(() =>
    props.channel === 'email' ? 'Email OTP' : 'Authenticator app (TOTP)'
);
</script>

<style scoped>
/* ── Action buttons ────────────────────────────────── */
.twofa-panel__actions {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.twofa-action-btn {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.75rem 1rem;
    background: var(--rw-surface-2);
    border: 1px solid var(--rw-border);
    border-radius: 0.75rem;
    cursor: pointer;
    text-align: left;
    transition: background 0.12s, border-color 0.12s;
    width: 100%;
}

.twofa-action-btn:hover:not(:disabled) {
    background: var(--rw-border);
    border-color: var(--rw-100);
}

.twofa-action-btn:disabled {
    opacity: 0.5;
    cursor: default;
}

.twofa-action-btn__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    background: var(--rw-50);
    border: 1px solid var(--rw-100);
    flex-shrink: 0;
}

.twofa-action-btn__body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.twofa-action-btn__body strong {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--rw-ink);
}

.twofa-action-btn__body span {
    font-size: 0.78rem;
    color: var(--rw-muted);
    line-height: 1.4;
}

/* ── Active channel row ────────────────────────────── */
.twofa-channel {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.875rem;
    background: var(--rw-50);
    border: 1px solid var(--rw-100);
    border-radius: 0.625rem;
    font-size: 0.82rem;
    color: var(--rw-muted);
    margin-bottom: 0.875rem;
}

.twofa-channel strong {
    color: var(--rw-700);
    font-weight: 600;
}

/* ── Disable row ───────────────────────────────────── */
.twofa-disable-row {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--rw-border);
}

.twofa-text-btn {
    background: none;
    border: none;
    padding: 0;
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--rw-muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    transition: color 0.12s;
}

.twofa-text-btn:hover {
    color: var(--rw-ink);
}

.twofa-text-btn--danger {
    color: #b91c1c;
}

.twofa-text-btn--danger:hover {
    color: #991b1b;
}

/* ── TOTP setup ────────────────────────────────────── */
.twofa-panel__setup {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1.25rem;
    align-items: start;
    margin-bottom: 1rem;
}

.twofa-panel__qr {
    width: 160px;
    height: 160px;
    border-radius: 0.875rem;
    border: 1px solid var(--rw-border);
    flex-shrink: 0;
}

.twofa-panel__copy {
    display: grid;
    gap: 0.75rem;
}

.twofa-step {
    margin: 0;
    font-size: 0.825rem;
    color: var(--rw-muted);
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    line-height: 1.5;
}

.twofa-step__num {
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

.twofa-panel__secret {
    display: block;
    padding: 0.45rem 0.75rem;
    background: var(--rw-surface-2);
    border: 1px solid var(--rw-border);
    border-radius: 0.5rem;
    font-size: 0.78rem;
    letter-spacing: 0.08em;
    color: var(--rw-ink-2);
    word-break: break-all;
}

.twofa-panel__verify {
    display: grid;
    gap: 0.875rem;
}

.twofa-panel__verify-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* ── Email setup ───────────────────────────────────── */
.twofa-email-setup {
    margin-bottom: 1rem;
}

.twofa-email-setup__notice {
    display: flex;
    align-items: flex-start;
    gap: 0.875rem;
    padding: 0.875rem 1rem;
    background: var(--rw-50);
    border: 1px solid var(--rw-100);
    border-radius: 0.75rem;
}

.twofa-email-setup__heading {
    margin: 0 0 0.25rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--rw-ink);
}

.twofa-email-setup__sub {
    margin: 0;
    font-size: 0.82rem;
    color: var(--rw-muted);
    line-height: 1.55;
}

.twofa-email-setup__sub strong {
    color: var(--rw-ink-2);
}

@media (max-width: 580px) {
    .twofa-panel__setup {
        grid-template-columns: 1fr;
    }
}
</style>
