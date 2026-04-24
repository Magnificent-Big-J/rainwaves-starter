<template>
    <div class="page-header">
        <div class="page-header__copy">
            <p v-if="eyebrow" class="page-header__eyebrow">{{ eyebrow }}</p>
            <h1 class="page-header__title">{{ title }}</h1>
            <p v-if="subtitle" class="page-header__subtitle">{{ subtitle }}</p>
        </div>

        <div v-if="$slots.metrics || $slots.actions" class="page-header__aside">
            <div v-if="$slots.metrics" class="page-header__metrics">
                <slot name="metrics" />
            </div>

            <div v-if="$slots.actions" class="page-header__actions">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    eyebrow: { type: String, default: null },
    title: { type: String, required: true },
    subtitle: { type: String, default: null },
});
</script>

<style scoped>
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.page-header__copy {
    max-width: 760px;
}

.page-header__eyebrow {
    margin: 0;
    color: var(--rw-600);
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.75rem;
    font-weight: 700;
}

.page-header__title {
    margin: 0.5rem 0 0;
    font-size: clamp(2rem, 4vw, 3.25rem);
    line-height: 1;
    letter-spacing: -0.03em;
    color: var(--rw-ink);
    font-weight: 800;
}

.page-header__subtitle {
    margin: 0.75rem 0 0;
    max-width: 60ch;
    color: var(--rw-muted);
    font-size: 0.95rem;
    line-height: 1.65;
}

.page-header__aside {
    display: grid;
    gap: 0.9rem;
    justify-items: end;
    flex: 1 1 260px;
}

.page-header__metrics,
.page-header__actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

@media (max-width: 900px) {
    .page-header__aside {
        justify-items: start;
    }

    .page-header__metrics,
    .page-header__actions {
        justify-content: flex-start;
    }
}
</style>
