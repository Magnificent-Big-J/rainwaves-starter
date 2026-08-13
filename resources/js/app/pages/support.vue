<template>
    <div class="support-page">
        <p class="eyebrow">Support</p>
        <h1 class="support-page__title">Need a hand?</h1>
        <p class="support-page__text">
            Reach out and we'll get back to you. If you're signed in, include your account email so we can find
            your account faster.
        </p>

        <div class="support-page__cards">
            <a class="support-card" :href="`mailto:${appConfig.brand.support_email}`">
                <v-icon icon="mdi-email-outline" size="22" />
                <div>
                    <h3>Email us</h3>
                    <p>{{ appConfig.brand.support_email }}</p>
                </div>
            </a>

            <RouterLink class="support-card" to="/notifications">
                <v-icon icon="mdi-bell-outline" size="22" />
                <div>
                    <h3>Notifications</h3>
                    <p>Service updates and account alerts land here.</p>
                </div>
            </RouterLink>
        </div>

        <div class="support-page__legal">
            <RouterLink :to="appConfig.brand.legal.terms_url">Terms of Service</RouterLink>
            <span>&middot;</span>
            <RouterLink :to="appConfig.brand.legal.privacy_url">Privacy Policy</RouterLink>
        </div>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "guest",
        "title": "Support"
    }
}
</route>

<script setup>
import { useAppConfigStore } from '../stores/app-config';

const appConfig = useAppConfigStore();
</script>

<style scoped>
.support-page {
    max-width: 640px;
    margin: 0 auto;
    padding: 3rem 1.5rem 5rem;
    text-align: center;
}

.eyebrow {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: rgba(0, 200, 140, 0.8);
    margin: 0 0 0.75rem;
}

.support-page__title {
    font-size: 2.2rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 0.75rem;
}

.support-page__text {
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.6;
    margin: 0 0 2.5rem;
}

.support-page__cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2.5rem;
}

.support-card {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1.25rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    text-decoration: none;
    color: rgba(255, 255, 255, 0.9);
    text-align: left;
    transition: background 0.15s, border-color 0.15s;
}

.support-card:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(0, 135, 95, 0.3);
}

.support-card h3 {
    font-size: 0.95rem;
    margin: 0 0 0.2rem;
}

.support-card p {
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.5);
    margin: 0;
}

.support-page__legal {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    font-size: 0.82rem;
}

.support-page__legal a {
    color: rgba(255, 255, 255, 0.5);
    text-decoration: none;
}

.support-page__legal a:hover {
    color: rgba(255, 255, 255, 0.85);
}

.support-page__legal span {
    color: rgba(255, 255, 255, 0.25);
}
</style>
