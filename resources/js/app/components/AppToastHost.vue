<template>
    <v-snackbar
        :model-value="Boolean(active)"
        :color="active?.color || 'info'"
        :timeout="active?.timeout || 5000"
        location="top right"
        rounded="xl"
        class="app-toast"
        @update:model-value="onToggle"
    >
        <div v-if="active" class="app-toast__inner">
            <v-icon :icon="active.icon" size="18" />
            <div class="app-toast__copy">
                <strong v-if="active.title">{{ active.title }}</strong>
                <span>{{ active.message }}</span>
            </div>
        </div>
    </v-snackbar>
</template>

<script setup>
import { storeToRefs } from 'pinia';

import { useNotificationsStore } from '../stores/notifications';

const notifications = useNotificationsStore();
const { activeToast: active } = storeToRefs(notifications);

const onToggle = (value) => {
    if (!value && active.value) {
        notifications.dismissToast(active.value.id);
    }
};
</script>

<style scoped>
.app-toast__inner {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.app-toast__copy {
    display: grid;
    gap: 0.1rem;
}

.app-toast__copy span {
    line-height: 1.45;
}
</style>
