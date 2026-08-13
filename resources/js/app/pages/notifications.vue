<template>
    <div class="notifications-page">
        <AppPageHeader
            eyebrow="Account"
            title="Notifications"
            subtitle="Full history of system, billing, and security notifications sent to your account."
        >
            <template #metrics>
                <AppStatusBadge
                    :status="notifications.unreadCount > 0 ? 'processing' : 'active'"
                    :label="`${notifications.unreadCount} unread`"
                />
            </template>
            <template #actions>
                <v-btn
                    variant="tonal"
                    size="small"
                    :disabled="notifications.unreadCount === 0"
                    @click="notifications.markAllRead()"
                >
                    Mark all read
                </v-btn>
            </template>
        </AppPageHeader>

        <AppSectionCard title="History">
            <div v-if="notifications.loading && !notifications.items.length" class="notifications-page__loading">
                <AppSkeleton v-for="n in 4" :key="n" height="72px" />
            </div>

            <div v-else-if="notifications.items.length" class="notifications-page__list">
                <AppNotificationItem
                    v-for="item in notifications.items"
                    :key="item.id"
                    :item="item"
                    @select="notifications.markRead(item.id)"
                />
            </div>

            <AppEmptyState
                v-else
                title="No notifications yet"
                text="System, billing, and security notifications will collect here."
                icon="mdi-bell-badge-outline"
            />

            <AppPaginationBar
                v-if="notifications.pagination && notifications.pagination.last_page > 1"
                :meta="notifications.pagination"
                @update:page="goToPage"
            />
        </AppSectionCard>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "contextual",
        "title": "Notifications",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { onMounted } from 'vue';

import AppEmptyState from '../components/AppEmptyState.vue';
import AppNotificationItem from '../components/AppNotificationItem.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPaginationBar from '../components/AppPaginationBar.vue';
import AppSectionCard from '../components/AppSectionCard.vue';
import AppSkeleton from '../components/AppSkeleton.vue';
import AppStatusBadge from '../components/AppStatusBadge.vue';
import { useNotificationsStore } from '../stores/notifications';

const notifications = useNotificationsStore();

const goToPage = (page) => notifications.fetch({ page });

onMounted(() => notifications.fetch());
</script>

<style scoped>
.notifications-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    max-width: 860px;
}

.notifications-page__loading {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
}

.notifications-page__list {
    display: flex;
    flex-direction: column;
}
</style>
