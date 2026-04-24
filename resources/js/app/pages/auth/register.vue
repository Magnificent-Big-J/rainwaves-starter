<template>
    <AuthCard
        eyebrow="New account"
        title="Create account"
        subtitle="Fill in your details to request access."
        :busy="loading"
    >
        <div v-if="disabled" class="reg-closed">
            <div class="reg-closed__icon">
                <v-icon size="28" color="warning">mdi-lock-outline</v-icon>
            </div>
            <div>
                <p class="reg-closed__heading">Registration is currently closed</p>
                <p class="reg-closed__text">
                    New accounts are created by an administrator.
                    Contact your team lead or sign in if you already have access.
                </p>
            </div>
            <v-btn variant="outlined" color="primary" to="/auth/login" block>
                Go to sign in
            </v-btn>
        </div>

        <template v-else>
            <FormStatusAlert :message="formMessage" :type="formMessageType" />

            <v-form class="auth-form" @submit.prevent="submit">
                <AppTextField
                    v-model="form.name"
                    label="Full name"
                    :error-messages="errors.name"
                    autocomplete="name"
                    required
                />

                <AppTextField
                    v-model="form.email"
                    label="Email address"
                    type="email"
                    :error-messages="errors.email"
                    autocomplete="email"
                    required
                />

                <AppTextField
                    v-model="form.password"
                    label="Password"
                    type="password"
                    password-toggle
                    :error-messages="errors.password"
                    autocomplete="new-password"
                    required
                />

                <AppTextField
                    v-model="form.password_confirmation"
                    label="Confirm password"
                    type="password"
                    password-toggle
                    autocomplete="new-password"
                    required
                />

                <FormActions
                    submit-label="Create account"
                    :loading="loading"
                    left-link-label="Already have an account?"
                    left-link-to="/auth/login"
                />
            </v-form>
        </template>
    </AuthCard>
</template>

<route lang="json">
{
    "meta": {
        "layout": "auth",
        "title": "Register",
        "guestOnly": true
    }
}
</route>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

import AuthCard from '../../components/AuthCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import FormActions from '../../components/FormActions.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import { api } from '../../utils/api';
import { csrfCookie, getXsrfToken, AUTH_BASE } from '../../stores/auth-shared';

const router = useRouter();

const loading = ref(false);
const disabled = ref(false);
const formMessage = ref('');
const formMessageType = ref('error');
const errors = ref({});

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = async () => {
    formMessage.value = '';
    errors.value = {};
    loading.value = true;

    try {
        await csrfCookie();

        await api(`${AUTH_BASE}/register`, {
            method: 'POST',
            body: form,
            headers: { 'X-XSRF-TOKEN': getXsrfToken() },
        });

        await router.push({ path: '/auth/login', query: { registered: '1' } });
    } catch (error) {
        const status = error?.response?.status ?? error?.status;
        const data = error?.data;

        if (status === 403 || data?.message?.toLowerCase().includes('disabled')) {
            disabled.value = true;
            return;
        }

        errors.value = data?.errors ?? {};
        formMessage.value = data?.message || 'Unable to create account. Please try again.';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.auth-form {
    display: grid;
    gap: 1rem;
}

.reg-closed {
    display: grid;
    gap: 1rem;
}

.reg-closed__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 999px;
    background: rgba(183, 121, 31, 0.1);
}

.reg-closed__heading {
    margin: 0 0 0.4rem;
    font-weight: 700;
    font-size: 1rem;
}

.reg-closed__text {
    margin: 0;
    font-size: 0.9rem;
    color: var(--starter-muted);
    line-height: 1.5;
}
</style>
