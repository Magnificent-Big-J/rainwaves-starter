<template>
    <v-card class="auth-card" rounded="xl">
        <div class="auth-card__inner">
            <BusyOverlay :active="busy" />

            <div>
                <p v-if="eyebrow" class="auth-card__eyebrow">{{ eyebrow }}</p>
                <h1 class="auth-card__title">{{ title }}</h1>
                <p v-if="subtitle" class="auth-card__subtitle">{{ subtitle }}</p>
            </div>

            <slot />
        </div>
    </v-card>
</template>

<script setup>
defineProps({
    busy: {
        type: Boolean,
        default: false,
    },
    eyebrow: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<style scoped>
.auth-card {
    width: 100%;
    max-width: 510px;
    position: relative;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(17, 34, 51, 0.08);
    box-shadow: 0 16px 38px rgba(31, 45, 65, 0.1);
}

.auth-card__inner {
    position: relative;
    display: grid;
    gap: 1.25rem;
    padding: 1.5rem;
}

.auth-card__eyebrow {
    margin: 0 0 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: var(--starter-accent);
    font-size: 0.78rem;
    font-weight: 700;
}

.auth-card__title {
    margin: 0;
    font-size: clamp(1.65rem, 3vw, 2.25rem);
    line-height: 1.02;
}

.auth-card__subtitle {
    margin: 0.75rem 0 0;
    color: var(--starter-muted);
}
</style>
