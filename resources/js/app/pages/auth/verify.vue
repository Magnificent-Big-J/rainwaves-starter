<template>
    <AuthCard
        eyebrow="Two-factor verification"
        title="Verify your sign-in"
        :subtitle="subtitle"
        :busy="twoFactor.loading"
    >
        <FormStatusAlert :message="message" :type="messageType" />

        <v-form class="auth-form" @submit.prevent="submit">
            <v-text-field
                v-model="code"
                label="Verification code"
                autocomplete="one-time-code"
                required
            />

            <FormActions
                submit-label="Verify"
                :loading="twoFactor.loading"
                left-link-label="Back to sign in"
                left-link-to="/auth/login"
            />
        </v-form>

        <v-btn
            variant="text"
            color="primary"
            :loading="twoFactor.loading"
            @click="resend"
        >
            Resend email code
        </v-btn>
    </AuthCard>
</template>

<route lang="json">
{
    "meta": {
        "layout": "auth",
        "title": "Verify sign-in",
        "guestOnly": true
    }
}
</route>

<script setup>
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

import { useSessionStore } from '../../stores/session';
import { useTwoFactorStore } from '../../stores/two-factor';

const router = useRouter();
const session = useSessionStore();
const twoFactor = useTwoFactorStore();

const code = ref('');
const message = ref('');
const messageType = ref('error');

const subtitle = computed(() => {
    if (session.pendingTwoFactorChannel === 'totp') {
        return 'Enter the code from your authenticator app.';
    }

    return 'Enter the one-time code sent through your configured channel.';
});

const submit = async () => {
    message.value = '';

    try {
        await twoFactor.verifyLoginCode(code.value);
        await router.push('/foundation');
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.data?.message || 'Verification failed.';
    }
};

const resend = async () => {
    message.value = '';

    try {
        await twoFactor.resendLoginCode();
        messageType.value = 'success';
        message.value = 'A new verification code has been sent.';
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.data?.message || 'Unable to resend the verification code.';
    }
};
</script>

<style scoped>
.auth-form {
    display: grid;
    gap: 1rem;
}
</style>
