<template>
    <div class="legal-page">
        <p class="eyebrow">Legal</p>
        <h1 class="legal-page__title">Terms of Service</h1>
        <p class="legal-page__updated">Starter placeholder — replace with your real terms before launch.</p>

        <div class="legal-page__body">
            <p>
                This is boilerplate content shipped with {{ appConfig.brand.name }} so the
                <code>/legal/terms</code> route resolves to something real instead of a 404. It is not legal
                advice and must be replaced with your organization's actual terms of service before this app
                accepts real users or payments.
            </p>

            <h2>What to cover</h2>
            <ul>
                <li>Account eligibility and acceptable use</li>
                <li>Billing terms if you use the PayFast checkout/subscription flows</li>
                <li>Service availability, warranty disclaimers, and limitation of liability</li>
                <li>How accounts can be suspended or terminated</li>
                <li>Governing law and jurisdiction</li>
            </ul>

            <p>
                Questions about these terms can be sent to
                <a :href="`mailto:${appConfig.brand.support_email}`">{{ appConfig.brand.support_email }}</a>.
            </p>
        </div>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "guest",
        "title": "Terms of Service"
    }
}
</route>

<script setup>
import { useAppConfigStore } from '../../stores/app-config';

const appConfig = useAppConfigStore();
</script>

<style scoped>
.legal-page {
    max-width: 720px;
    margin: 0 auto;
    padding: 3rem 1.5rem 5rem;
}

.eyebrow {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: rgba(0, 200, 140, 0.8);
    margin: 0 0 0.75rem;
}

.legal-page__title {
    font-size: 2.2rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 0.5rem;
}

.legal-page__updated {
    color: rgba(255, 255, 255, 0.45);
    font-size: 0.85rem;
    margin: 0 0 2.5rem;
}

.legal-page__body {
    color: rgba(255, 255, 255, 0.68);
    line-height: 1.7;
}

.legal-page__body h2 {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.2rem;
    margin: 2rem 0 0.75rem;
}

.legal-page__body ul {
    padding-left: 1.25rem;
}

.legal-page__body li {
    margin-bottom: 0.4rem;
}

.legal-page__body code {
    background: rgba(255, 255, 255, 0.08);
    padding: 0.1rem 0.4rem;
    border-radius: 0.3rem;
    font-size: 0.9em;
}

.legal-page__body a {
    color: rgba(0, 200, 140, 0.9);
}
</style>
