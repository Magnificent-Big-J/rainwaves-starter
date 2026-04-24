<template>
    <div class="empty-state">
        <div class="empty-state__icon">
            <v-icon :icon="icon" size="28" />
        </div>
        <h3 class="empty-state__title">{{ title }}</h3>
        <p v-if="text" class="empty-state__text">{{ text }}</p>
        <div v-if="$slots.actions" class="empty-state__actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    icon: { type: String, default: 'mdi-layers-search-outline' },
    title: { type: String, default: 'Nothing here yet' },
    text: { type: String, default: null },
});
</script>

<style scoped>
.empty-state {
    display: grid;
    justify-items: center;
    gap: 0.85rem;
    padding: 2.5rem 1.25rem;
    text-align: center;
}

.empty-state__icon {
    width: 3.2rem;
    height: 3.2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: var(--rw-50);
    color: var(--rw-600);
}

.empty-state__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--rw-ink);
}

.empty-state__text {
    margin: 0;
    max-width: 44ch;
    color: var(--rw-muted);
    line-height: 1.6;
}

.empty-state__actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    justify-content: center;
}
</style>
