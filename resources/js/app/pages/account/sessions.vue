<template>
    <div class="sessions-page">
        <AppPageHeader
            eyebrow="Account"
            title="Sessions"
            subtitle="Browsers currently signed in to your account. If you don't recognize one, revoke it."
        >
            <template #actions>
                <v-btn
                    variant="tonal"
                    size="small"
                    color="error"
                    :disabled="otherSessions.length === 0"
                    @click="confirmRevokeOthers = true"
                >
                    Sign out other sessions
                </v-btn>
            </template>
        </AppPageHeader>

        <AppSectionCard title="Active sessions">
            <div v-if="sessions.loading && !sessions.items.length" class="sessions-page__loading">
                <AppSkeleton v-for="n in 3" :key="n" height="64px" />
            </div>

            <div v-else-if="sessions.items.length" class="sessions-page__list">
                <div v-for="item in sessions.items" :key="item.id" class="session-row">
                    <div class="session-row__icon">
                        <v-icon icon="mdi-monitor-cellphone" size="20" />
                    </div>
                    <div class="session-row__meta">
                        <div class="session-row__title">
                            <span>{{ item.ip_address || 'Unknown IP' }}</span>
                            <AppStatusBadge v-if="item.is_current" status="active" label="This device" />
                        </div>
                        <span class="session-row__ua">{{ item.user_agent || 'Unknown browser' }}</span>
                        <span class="session-row__time">Last active {{ formatTime(item.last_active_at) }}</span>
                    </div>
                    <v-btn
                        v-if="!item.is_current"
                        variant="text"
                        size="small"
                        color="error"
                        @click="revokeTarget = item"
                    >
                        Revoke
                    </v-btn>
                </div>
            </div>

            <AppEmptyState
                v-else
                title="No active sessions"
                text="Sign in from a browser to see it listed here."
                icon="mdi-monitor-off"
            />
        </AppSectionCard>

        <ConfirmDialog
            :model-value="Boolean(revokeTarget)"
            title="Revoke this session?"
            text="That browser will be signed out immediately."
            confirm-label="Revoke"
            confirm-color="error"
            @update:model-value="revokeTarget = null"
            @cancel="revokeTarget = null"
            @confirm="doRevoke"
        />

        <ConfirmDialog
            v-model="confirmRevokeOthers"
            title="Sign out other sessions?"
            text="Every browser except this one will be signed out immediately."
            confirm-label="Sign out others"
            confirm-color="error"
            @confirm="doRevokeOthers"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "contextual",
        "title": "Sessions",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';

import AppEmptyState from '../../components/AppEmptyState.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSkeleton from '../../components/AppSkeleton.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import { useSessionsStore } from '../../stores/sessions';

const sessions = useSessionsStore();
const revokeTarget = ref(null);
const confirmRevokeOthers = ref(false);

const otherSessions = computed(() => sessions.items.filter((item) => !item.is_current));

const formatTime = (iso) => (iso ? new Date(iso).toLocaleString() : 'Unknown');

const doRevoke = async () => {
    if (revokeTarget.value) {
        await sessions.revoke(revokeTarget.value.id);
    }
    revokeTarget.value = null;
};

const doRevokeOthers = async () => {
    await sessions.revokeOthers();
    confirmRevokeOthers.value = false;
};

onMounted(() => sessions.fetch());
</script>

<style scoped>
/* Capped reading width, not page boxing (see notifications.vue for the same
   reasoning) — wider than that page specifically because .session-row__ua
   truncates with an ellipsis, and user-agent strings are long enough that
   the extra room noticeably reduces how often that truncation kicks in. */
.sessions-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    max-width: 1100px;
}

.sessions-page__loading {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
}

.sessions-page__list {
    display: flex;
    flex-direction: column;
}

.session-row {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.9rem 0.25rem;
    border-bottom: 1px solid var(--rw-border);
}

.session-row:last-child {
    border-bottom: none;
}

.session-row__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.75rem;
    background: var(--rw-surface-2);
    color: var(--rw-muted);
    flex-shrink: 0;
}

.session-row__meta {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    flex: 1;
    min-width: 0;
}

.session-row__title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rw-ink);
}

.session-row__ua {
    font-size: 0.8rem;
    color: var(--rw-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.session-row__time {
    font-size: 0.75rem;
    color: var(--rw-dim);
}
</style>
