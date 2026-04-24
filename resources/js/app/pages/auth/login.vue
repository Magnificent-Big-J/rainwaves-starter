<template>
    <AuthCard
        eyebrow="Session access"
        title="Sign in"
        subtitle="Use your account credentials to start a secure session."
        :busy="session.loading"
    >
        <FormStatusAlert :message="formMessage" />

        <v-form class="auth-form" @submit.prevent="submit">
            <v-text-field
                v-model="form.email"
                label="Email"
                type="email"
                autocomplete="email"
                required
            />

            <v-text-field
                v-model="form.password"
                label="Password"
                type="password"
                autocomplete="current-password"
                required
            />

            <FormActions
                submit-label="Sign in"
                :loading="session.loading"
                left-link-label="Forgot password?"
                left-link-to="/auth/forgot-password"
            />
        </v-form>
    </AuthCard>
</template>

<route lang="json">
{
    "meta": {
        "layout": "auth",
        "title": "Sign in",
        "guestOnly": true
    }
}
</route>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import { useSessionStore } from '../../stores/session';

const router = useRouter();
const session = useSessionStore();

const form = reactive({
    email: '',
    password: '',
});

const formMessage = ref('');

const submit = async () => {
    formMessage.value = '';

    try {
        const response = await session.login(form);

        if (response?.status === '2fa_required') {
            await router.push('/auth/verify');

            return;
        }

        await router.push('/foundation');
    } catch (error) {
        formMessage.value = error?.data?.message || 'Unable to sign in with those credentials.';
    }
};
</script>

<style scoped>
.auth-form {
    display: grid;
    gap: 1rem;
}
</style>
