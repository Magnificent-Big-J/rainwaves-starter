<template>
    <v-dialog
        :model-value="modelValue"
        :max-width="maxWidth"
        :persistent="persistent"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <v-card class="app-modal">
            <div v-if="title || $slots.header" class="app-modal__header">
                <div>
                    <h3 v-if="title" class="app-modal__title">{{ title }}</h3>
                    <p v-if="subtitle" class="app-modal__subtitle">{{ subtitle }}</p>
                    <slot name="header" />
                </div>
                <v-btn icon="mdi-close" variant="text" @click="$emit('update:modelValue', false)" />
            </div>

            <v-card-text class="app-modal__body">
                <slot />
            </v-card-text>

            <v-card-actions v-if="$slots.actions" class="app-modal__actions">
                <slot name="actions" />
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
    maxWidth: { type: [Number, String], default: 520 },
    persistent: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<style scoped>
.app-modal {
    background: rgba(255, 253, 248, 0.98);
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.app-modal__header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem 0;
}

.app-modal__title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
}

.app-modal__subtitle {
    margin: 0.35rem 0 0;
    color: var(--rw-muted);
    font-size: 0.86rem;
}

.app-modal__body {
    padding-top: 1rem;
}

.app-modal__actions {
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0 1.25rem 1.25rem;
}
</style>
