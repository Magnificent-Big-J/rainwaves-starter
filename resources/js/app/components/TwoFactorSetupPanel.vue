<template>
    <AppSectionCard title="Two-factor authentication" subtitle="Protect accounts with authenticator apps and email OTP challenges.">
        <template #header>
            <AppStatusBadge
                :status="enabled ? 'enabled' : 'inactive'"
                :label="enabled ? 'Enabled' : 'Disabled'"
            />
        </template>

        <FormStatusAlert :message="message" :type="messageType" />

        <template v-if="!secret">
            <div class="twofa-panel__actions">
                <v-btn prepend-icon="mdi-qrcode" :loading="loading" @click="$emit('start')">
                    Set up authenticator app
                </v-btn>
                <v-btn variant="outlined" prepend-icon="mdi-email-outline" :loading="loading" @click="$emit('email')">
                    Send email OTP
                </v-btn>
                <v-btn
                    v-if="enabled"
                    color="error"
                    variant="tonal"
                    prepend-icon="mdi-shield-remove-outline"
                    :loading="loading"
                    @click="$emit('disable')"
                >
                    Disable 2FA
                </v-btn>
            </div>
        </template>

        <template v-else>
            <div class="twofa-panel__setup">
                <div class="twofa-panel__qr-wrap">
                    <img
                        v-if="qrCodeDataUrl"
                        :src="qrCodeDataUrl"
                        class="twofa-panel__qr"
                        alt="QR code"
                    />
                </div>
                <div class="twofa-panel__copy">
                    <p>Scan the QR code with your authenticator app, or enter the secret manually.</p>
                    <code class="twofa-panel__secret">{{ secret }}</code>
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
                    <v-btn variant="text" @click="$emit('cancel')">Cancel</v-btn>
                </div>
            </v-form>
        </template>
    </AppSectionCard>
</template>

<script setup>
defineProps({
    enabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    secret: { type: String, default: null },
    qrCodeDataUrl: { type: String, default: null },
    code: { type: String, default: '' },
    message: { type: String, default: '' },
    messageType: { type: String, default: 'success' },
});

defineEmits(['start', 'email', 'disable', 'verify', 'cancel', 'update:code']);
</script>

<style scoped>
.twofa-panel__actions,
.twofa-panel__verify-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.twofa-panel__setup {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1rem;
    align-items: center;
}

.twofa-panel__qr {
    width: 180px;
    height: 180px;
    border-radius: 1rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.twofa-panel__copy {
    display: grid;
    gap: 0.75rem;
}

.twofa-panel__copy p {
    margin: 0;
    line-height: 1.6;
    color: var(--starter-muted);
}

.twofa-panel__secret {
    display: inline-flex;
    padding: 0.75rem 0.9rem;
    background: rgba(17, 34, 51, 0.06);
    border-radius: 0.9rem;
}

.twofa-panel__verify {
    display: grid;
    gap: 0.85rem;
    margin-top: 1rem;
}

@media (max-width: 720px) {
    .twofa-panel__setup {
        grid-template-columns: 1fr;
    }
}
</style>
