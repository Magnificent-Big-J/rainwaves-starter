<template>
    <v-dialog :model-value="modelValue" max-width="460" @update:model-value="$emit('update:modelValue', $event)">
        <v-card class="confirm-dialog">
            <v-card-title class="confirm-dialog__title">{{ title }}</v-card-title>
            <v-card-text class="confirm-dialog__body">
                <p class="confirm-dialog__text">{{ text }}</p>
                <slot />
            </v-card-text>
            <v-card-actions class="confirm-dialog__actions">
                <v-btn variant="text" @click="$emit('cancel')">{{ cancelLabel }}</v-btn>
                <v-btn :color="confirmColor" :loading="loading" @click="$emit('confirm')">
                    {{ confirmLabel }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, required: true },
    text: { type: String, default: null },
    confirmLabel: { type: String, default: 'Confirm' },
    cancelLabel: { type: String, default: 'Cancel' },
    confirmColor: { type: String, default: 'primary' },
    loading: { type: Boolean, default: false },
});

defineEmits(['update:modelValue', 'confirm', 'cancel']);
</script>

<style scoped>
.confirm-dialog {
    background: rgba(255, 253, 248, 0.98);
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.confirm-dialog__title {
    font-size: 1.1rem;
    font-weight: 700;
}

.confirm-dialog__body {
    display: grid;
    gap: 1rem;
}

.confirm-dialog__text {
    margin: 0;
    color: var(--starter-muted, rgba(17, 34, 51, 0.7));
    line-height: 1.65;
}

.confirm-dialog__actions {
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 0 1.25rem 1.25rem;
}
</style>
