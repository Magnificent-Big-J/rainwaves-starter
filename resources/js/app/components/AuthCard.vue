<template>
    <div class="auth-card">
        <BusyOverlay :active="busy" />

        <div class="auth-card__head">
            <p v-if="eyebrow" class="auth-card__eyebrow">{{ eyebrow }}</p>
            <h1 class="auth-card__title">{{ title }}</h1>
            <p v-if="subtitle" class="auth-card__subtitle">{{ subtitle }}</p>
        </div>

        <slot />
    </div>
</template>

<script setup>
import BusyOverlay from './BusyOverlay.vue';

defineProps({
    busy:     { type: Boolean, default: false },
    eyebrow:  { type: String,  default: '' },
    title:    { type: String,  required: true },
    subtitle: { type: String,  default: '' },
});
</script>

<style scoped>
.auth-card {
    position: relative;
    width: 100%;
    max-width: 440px;
    background: rgba(255, 255, 255, 0.97);
    border-radius: 1.25rem;
    padding: 2rem 2.25rem 2.25rem;
    box-shadow:
        0 0 0 1px rgba(255, 255, 255, 0.06),
        0 24px 56px rgba(0, 0, 0, 0.45),
        0 8px 20px rgba(0, 0, 0, 0.25);
}

.auth-card__head {
    margin-bottom: 1.5rem;
}

.auth-card__eyebrow {
    margin: 0 0 0.6rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: var(--rw-600);
}

.auth-card__title {
    margin: 0;
    font-size: clamp(1.5rem, 3vw, 1.9rem);
    font-weight: 800;
    color: var(--rw-ink);
    line-height: 1.1;
    letter-spacing: -0.02em;
}

.auth-card__subtitle {
    margin: 0.6rem 0 0;
    font-size: 0.875rem;
    color: var(--rw-muted);
    line-height: 1.55;
}
</style>
