<template>
    <v-card class="section-card">
        <div v-if="title || subtitle || $slots.header || $slots.actions" class="section-card__header">
            <div>
                <v-card-title v-if="title" class="section-card__title">
                    {{ title }}
                </v-card-title>
                <v-card-subtitle v-if="subtitle" class="section-card__sub">
                    {{ subtitle }}
                </v-card-subtitle>
                <slot name="header" />
            </div>

            <div v-if="$slots.actions" class="section-card__header-actions">
                <slot name="actions" />
            </div>
        </div>
        <v-card-text>
            <slot />
        </v-card-text>
    </v-card>
</template>

<script setup>
defineProps({
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
});
</script>

<style scoped>
.section-card {
    background: rgba(255, 253, 248, 0.9);
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.section-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1rem 0;
}

.section-card__title {
    font-size: 1rem;
    font-weight: 700;
    padding-bottom: 0;
}

.section-card__sub {
    padding-top: 0.25rem;
    font-size: 0.875rem;
}

.section-card__header-actions {
    gap: 0.5rem;
    display: flex;
    flex-wrap: wrap;
}
</style>
