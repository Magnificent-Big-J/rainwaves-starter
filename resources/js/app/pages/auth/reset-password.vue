<template>
    <AuthCard
        eyebrow="Reset password"
        title="Set a new password"
        subtitle="Complete the recovery flow with the token provided by the auth package."
        :busy="submitting"
    >
        <FormStatusAlert :message="message" :type="messageType" />

        <v-form class="auth-form" @submit.prevent="submit">
            <v-text-field v-model="form.email" label="Email" type="email" required />
            <v-text-field v-model="form.token" label="Reset token" required />
            <v-text-field
                v-model="form.password"
                label="New password"
                type="password"
                autocomplete="new-password"
                required
            />
            <v-text-field
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                autocomplete="new-password"
                required
            />

            <FormActions
                submit-label="Reset password"
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
        "title": "Reset password",
        "guestOnly": true
    }
}
</route>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import { api } from '../../utils/api';
import { AUTH_BASE } from '../../stores/auth-shared';

const router = useRouter();
const submitting = ref(false);
const message = ref('');
const messageType = ref('success');

const form = reactive({
    email: '',
    token: '',
    password: '',
    password_confirmation: '',
});

const submit = async () => {
    submitting.value = true;
    message.value = '';

    try {
        await api(`${AUTH_BASE}/reset-password`, {
            method: 'POST',
            body: form,
        });

        messageType.value = 'success';
        message.value = 'Password reset complete. You can sign in now.';

        setTimeout(() => {
            router.push('/auth/login');
        }, 400);
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.data?.message || 'Unable to reset the password.';
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
