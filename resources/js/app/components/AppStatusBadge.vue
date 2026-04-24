<template>
    <v-chip
        :color="resolved.color"
        :variant="variant"
        size="small"
        class="status-badge"
    >
        <template v-if="resolved.icon" #prepend>
            <v-icon :icon="resolved.icon" size="14" />
        </template>

        {{ resolved.label }}
    </v-chip>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: null },
    color: { type: String, default: null },
    icon: { type: String, default: null },
    variant: { type: String, default: 'tonal' },
});

const presetMap = {
    active: { color: 'success', icon: 'mdi-check-circle-outline' },
    enabled: { color: 'success', icon: 'mdi-check-circle-outline' },
    paid: { color: 'success', icon: 'mdi-check-decagram-outline' },
    complete: { color: 'success', icon: 'mdi-check-decagram-outline' },
    completed: { color: 'success', icon: 'mdi-check-decagram-outline' },
    pending: { color: 'warning', icon: 'mdi-timer-sand' },
    processing: { color: 'info', icon: 'mdi-progress-clock' },
    queued: { color: 'info', icon: 'mdi-progress-clock' },
    trialing: { color: 'info', icon: 'mdi-flask-outline' },
    draft: { color: 'secondary', icon: 'mdi-file-document-outline' },
    inactive: { color: 'secondary', icon: 'mdi-minus-circle-outline' },
    cancelled: { color: 'error', icon: 'mdi-cancel' },
    canceled: { color: 'error', icon: 'mdi-cancel' },
    failed: { color: 'error', icon: 'mdi-alert-circle-outline' },
    suspended: { color: 'error', icon: 'mdi-pause-circle-outline' },
    refunded: { color: 'secondary', icon: 'mdi-backup-restore' },
};

const humanize = (value) =>
    value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());

const resolved = computed(() => {
    const key = props.status.toLowerCase();
    const preset = presetMap[key] || { color: 'secondary', icon: 'mdi-circle-medium' };

    return {
        color: props.color || preset.color,
        icon: props.icon ?? preset.icon,
        label: props.label || humanize(props.status),
    };
});
</script>

<style scoped>
.status-badge {
    font-weight: 700;
    letter-spacing: 0.01em;
}
</style>
