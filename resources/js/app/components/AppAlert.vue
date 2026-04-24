<template>
    <v-alert
        v-if="message"
        :type="type"
        :variant="variant"
        :title="title"
        :closable="closable"
        class="app-alert"
        @click:close="$emit('close')"
    >
        <slot>
            {{ message }}
        </slot>
    </v-alert>
</template>

<script setup>
defineProps({
    title: { type: String, default: null },
    message: { type: String, default: null },
    type: { type: String, default: 'info' },
    variant: { type: String, default: 'tonal' },
    closable: { type: Boolean, default: false },
});

defineEmits(['close']);
</script>

<style scoped>
.app-alert {
    border-radius: 1rem;
}
</style>
