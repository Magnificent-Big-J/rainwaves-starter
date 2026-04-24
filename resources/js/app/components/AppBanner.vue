<template>
    <div v-if="message" class="app-banner" :class="`app-banner--${type}`">
        <div class="app-banner__copy">
            <v-icon :icon="icon" size="18" />
            <div>
                <strong v-if="title" class="app-banner__title">{{ title }}</strong>
                <p class="app-banner__message">{{ message }}</p>
            </div>
        </div>
        <div class="app-banner__actions">
            <slot name="actions" />
            <v-btn
                v-if="closable"
                icon="mdi-close"
                size="small"
                variant="text"
                @click="$emit('close')"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: null },
    message: { type: String, default: null },
    type: { type: String, default: 'info' },
    closable: { type: Boolean, default: false },
});

defineEmits(['close']);

const icon = computed(() => ({
    success: 'mdi-check-circle-outline',
    error: 'mdi-alert-circle-outline',
    warning: 'mdi-alert-outline',
    info: 'mdi-information-outline',
}[props.type] || 'mdi-information-outline'));
</script>

<style scoped>
.app-banner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.app-banner--success { background: rgba(21, 128, 61, 0.08); }
.app-banner--error { background: rgba(185, 28, 28, 0.08); }
.app-banner--warning { background: rgba(180, 83, 9, 0.08); }
.app-banner--info { background: rgba(3, 105, 161, 0.08); }

.app-banner__copy {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.app-banner__title {
    display: block;
    margin-bottom: 0.15rem;
}

.app-banner__message {
    margin: 0;
    line-height: 1.55;
}

.app-banner__actions {
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
</style>
