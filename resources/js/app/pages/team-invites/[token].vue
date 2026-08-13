<template>
    <AuthCard eyebrow="Team invitation" title="You've been invited" :subtitle="subtitle">
        <FormStatusAlert :message="message" :type="messageType" />

        <template v-if="session.isAuthenticated">
            <p class="invite-copy">
                Accept this invitation to join the team as your account, {{ session.user?.email }}.
            </p>
            <v-btn color="primary" block :loading="accepting" @click="accept">Accept invitation</v-btn>
        </template>

        <template v-else>
            <p class="invite-copy">Already have an account with this email?</p>
            <v-btn class="invite-signin" color="primary" variant="outlined" block :to="loginTo"
                >Sign in to accept</v-btn
            >

            <v-divider class="invite-divider">or create an account</v-divider>

            <v-form class="invite-form" @submit.prevent="register">
                <AppTextField v-model="registerForm.name" label="Full name" autocomplete="name" required />
                <AppTextField
                    :model-value="email"
                    label="Email"
                    type="email"
                    disabled
                    hint="This invitation was sent to this address."
                    persistent-hint
                />
                <AppTextField
                    v-model="registerForm.password"
                    label="Password"
                    type="password"
                    password-toggle
                    autocomplete="new-password"
                    required
                />
                <AppTextField
                    v-model="registerForm.password_confirmation"
                    label="Confirm password"
                    type="password"
                    password-toggle
                    autocomplete="new-password"
                    required
                />
                <v-btn color="primary" block type="submit" :loading="registering">Create account &amp; join team</v-btn>
            </v-form>
        </template>
    </AuthCard>
</template>

<route lang="json">
{
    "meta": {
        "layout": "auth",
        "title": "Team Invitation"
    }
}
</route>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import AppTextField from '../../components/AppTextField.vue';
import AuthCard from '../../components/AuthCard.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import { useSessionStore } from '../../stores/session';
import { useTeamStore } from '../../stores/team';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const team = useTeamStore();

const message = ref('');
const messageType = ref('error');
const accepting = ref(false);
const registering = ref(false);

const email = typeof route.query.email === 'string' ? route.query.email : '';
const redirectTarget = `/team-invites/${route.params.token}`;

const registerForm = reactive({
    name: '',
    password: '',
    password_confirmation: '',
});

const subtitle = computed(() =>
    session.isAuthenticated
        ? "Someone invited you to join their team — accept below if you're expecting this."
        : 'Sign in, or create an account below, to accept this invitation.'
);

const loginTo = computed(() => ({
    path: '/auth/login',
    query: { redirect: redirectTarget, ...(email ? { email } : {}) },
}));

const accept = async () => {
    message.value = '';
    accepting.value = true;

    const result = await team.acceptInvite(route.params.token);

    accepting.value = false;

    if (result.ok) {
        router.push('/account/team');

        return;
    }

    messageType.value = 'error';
    message.value = result.message;
};

const register = async () => {
    message.value = '';
    registering.value = true;

    const result = await team.registerAndAccept(route.params.token, registerForm);

    registering.value = false;

    if (result.ok) {
        router.push('/account/team');

        return;
    }

    messageType.value = 'error';
    message.value = result.message;
};
</script>

<style scoped>
.invite-copy {
    margin: 0 0 1rem;
    color: var(--rw-muted);
    font-size: 0.9rem;
    line-height: 1.5;
}

.invite-signin {
    margin-bottom: 1.25rem;
}

.invite-divider {
    margin-bottom: 1.25rem;
    font-size: 0.78rem;
    color: var(--rw-dim);
}

.invite-form {
    display: grid;
    gap: 1rem;
}
</style>
