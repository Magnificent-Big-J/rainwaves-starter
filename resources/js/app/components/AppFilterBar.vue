<template>
    <div class="filter-bar">
        <div class="filter-bar__primary">
            <slot />
        </div>
        <div v-if="$slots.actions" class="filter-bar__actions">
            <slot name="actions" />
        </div>
    </div>
</template>

<style scoped>
.filter-bar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.9rem;
    flex-wrap: wrap;
    padding: 1rem 1.1rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
    border-radius: 1rem;
    background: rgba(255, 253, 248, 0.9);
}

.filter-bar__primary,
.filter-bar__actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: flex-end;
}
</style>
