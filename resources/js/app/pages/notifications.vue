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

        <div class="notifications-page__stats">
            <AppStatCard
                label="Total"
                :value="String(totalCount)"
                helper="All notifications on this account"
                icon="mdi-bell-outline"
                status="active"
            />
            <AppStatCard
                label="Unread"
                :value="String(notifications.unreadCount)"
                helper="Not yet marked as read"
                icon="mdi-bell-badge-outline"
                :status="notifications.unreadCount > 0 ? 'processing' : 'active'"
            />
        </div>

        <AppSectionCard title="History">
            <template #actions>
                <v-switch
                    v-model="unreadOnly"
                    label="Unread only"
                    color="primary"
                    density="compact"
                    hide-details
                    @update:model-value="reload"
                />
            </template>

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
                v-else-if="unreadOnly"
                title="No unread notifications"
                text="You're all caught up — switch off 'Unread only' to see the full history."
                icon="mdi-bell-check-outline"
            />

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
import { onMounted, ref } from 'vue';

import AppEmptyState from '../components/AppEmptyState.vue';
import AppNotificationItem from '../components/AppNotificationItem.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppPaginationBar from '../components/AppPaginationBar.vue';
import AppSectionCard from '../components/AppSectionCard.vue';
import AppSkeleton from '../components/AppSkeleton.vue';
import AppStatCard from '../components/AppStatCard.vue';
import AppStatusBadge from '../components/AppStatusBadge.vue';
import { useNotificationsStore } from '../stores/notifications';

const notifications = useNotificationsStore();
const unreadOnly = ref(false);
// The "Total" stat must stay the true all-time count, not the currently-filtered
// pagination total (that would otherwise silently show "2" while "Unread only" is
// on, even though the account has 3 notifications) — only ever updated from an
// *unfiltered* fetch, so it stays accurate without being corrupted by the filter.
const totalCount = ref(0);

const syncTotalCount = () => {
    if (!unreadOnly.value) {
        totalCount.value = notifications.pagination?.total ?? notifications.items.length;
    }
};

const reload = async () => {
    await notifications.fetch({ unread: unreadOnly.value });
    syncTotalCount();
};

const goToPage = async (page) => {
    await notifications.fetch({ page, unread: unreadOnly.value });
    syncTotalCount();
};

onMounted(async () => {
    await notifications.fetch();
    syncTotalCount();
});
</script>

<style scoped>
.notifications-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.notifications-page__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
    max-width: 640px;
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

@media (max-width: 600px) {
    .notifications-page__stats {
        grid-template-columns: 1fr;
    }
}
</style>
