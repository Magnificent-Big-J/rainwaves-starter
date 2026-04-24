<template>
    <AuthCard
        eyebrow="Password reset"
        title="Recover access"
        subtitle="Request a reset link or code through the auth package flow."
        :busy="submitting"
    >
        <FormStatusAlert :message="message" :type="messageType" />

        <v-form class="auth-form" @submit.prevent="submit">
            <v-text-field
                v-model="email"
                label="Email"
                type="email"
                autocomplete="email"
                required
            />

            <FormActions
                submit-label="Send reset instructions"
                :loading="submitting"
                right-link-label="Back to sign in"
                right-link-to="/auth/login"
            />
        </v-form>
    </AuthCard>
</template>

<route lang="json">
{
    "meta": {
        "layout": "auth",
        "title": "Forgot password",
        "guestOnly": true
    }
}
</route>

<script setup>
import { ref } from 'vue';

import { api } from '../../utils/api';
import { PASSWORD_BASE } from '../../stores/auth-shared';

const email = ref('');
const message = ref('');
const messageType = ref('success');
const submitting = ref(false);

const submit = async () => {
    submitting.value = true;
    message.value = '';

    try {
        await api(`${PASSWORD_BASE}/forgot`, {
            method: 'POST',
            body: {
                email: email.value,
            },
        });

        messageType.value = 'success';
        message.value = 'If the account exists, reset instructions have been sent.';
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.data?.message || 'Unable to start password recovery.';
    } finally {
        submitting.value = false;
    }
};
</script>

<style scoped>
.auth-form {
    display: grid;
    gap: 1rem;
}
</style>
