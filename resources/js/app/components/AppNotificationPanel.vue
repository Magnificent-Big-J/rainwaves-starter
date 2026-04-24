<template>
    <v-menu
        v-model="open"
        :close-on-content-click="false"
        location="bottom end"
        offset="12"
    >
        <template #activator="{ props }">
            <v-btn v-bind="props" icon variant="text" class="notification-trigger">
                <v-badge
                    :model-value="notifications.unreadCount > 0"
                    :content="notifications.unreadCount"
                    color="error"
                    floating
                >
                    <v-icon icon="mdi-bell-outline" />
                </v-badge>
            </v-btn>
        </template>

        <v-card class="notification-panel">
            <div class="notification-panel__header">
                <div>
                    <strong>Notifications</strong>
                    <p>{{ notifications.unreadCount }} unread</p>
                </div>
                <div class="notification-panel__actions">
                    <v-btn variant="text" size="small" @click="notifications.markAllAsRead()">Mark all read</v-btn>
                    <v-btn variant="text" size="small" @click="notifications.clearAll()">Clear</v-btn>
                </div>
            </div>

            <div v-if="notifications.items.length" class="notification-panel__list">
                <AppNotificationItem
                    v-for="item in notifications.items"
                    :key="item.id"
                    :item="item"
                    @select="selectItem"
                />
            </div>

            <AppEmptyState
                v-else
                title="No notifications yet"
                text="System, billing, and security notifications will collect here."
                icon="mdi-bell-badge-outline"
            />
        </v-card>
    </v-menu>
</template>

<script setup>
import { ref } from 'vue';

import { useNotificationsStore } from '../stores/notifications';

const notifications = useNotificationsStore();
const open = ref(false);

const selectItem = (item) => {
    notifications.markAsRead(item.id);
};
</script>

<style scoped>
.notification-trigger {
    color: var(--rw-muted);
}

.notification-panel {
    width: min(420px, calc(100vw - 2rem));
    max-height: min(640px, 80vh);
    overflow: hidden;
    background: rgba(255, 253, 248, 0.98);
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.notification-panel__header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid rgba(17, 34, 51, 0.06);
}

.notification-panel__header p {
    margin: 0.2rem 0 0;
    font-size: 0.8rem;
    color: var(--starter-muted);
}

.notification-panel__actions {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.notification-panel__list {
    overflow: auto;
    max-height: min(560px, 70vh);
}
</style>
