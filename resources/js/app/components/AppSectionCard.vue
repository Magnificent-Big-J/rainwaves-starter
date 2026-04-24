<template>
    <div class="section-card">
        <div v-if="title || subtitle || $slots.header || $slots.actions" class="section-card__header">
            <div class="section-card__head-copy">
                <h3 v-if="title" class="section-card__title">{{ title }}</h3>
                <p v-if="subtitle" class="section-card__sub">{{ subtitle }}</p>
                <slot name="header" />
            </div>
            <div v-if="$slots.actions" class="section-card__actions">
                <slot name="actions" />
            </div>
        </div>
        <div class="section-card__body">
            <slot />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
});
</script>

<style scoped>
.section-card {
    background: var(--rw-surface);
    border: 1px solid var(--rw-border);
    border-radius: 1rem;
    box-shadow: var(--rw-shadow-xs);
}

.section-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem 0;
}

.section-card__head-copy {
    min-width: 0;
}

.section-card__title {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--rw-ink);
    letter-spacing: -0.01em;
}

.section-card__sub {
    margin: 0.3rem 0 0;
    font-size: 0.82rem;
    color: var(--rw-muted);
    line-height: 1.5;
}

.section-card__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    flex-shrink: 0;
}

.section-card__body {
    padding: 1.25rem 1.5rem 1.5rem;
}
</style>
