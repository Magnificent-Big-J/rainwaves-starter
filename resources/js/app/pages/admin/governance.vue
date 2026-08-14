<template>
    <div class="admin-governance-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Administration"
                title="Governance"
                subtitle="Role elevation requires a second approver — review pending requests to grant admin or super-admin access below."
            >
                <template #metrics>
                    <AppStatusBadge status="processing" :label="`${store.meta.total} pending`" />
                </template>
            </AppPageHeader>

            <AppSectionCard
                title="Pending role approvals"
                subtitle="Someone else must approve — the original requester cannot."
            >
                <div v-if="store.loading" class="request-list">
                    <AppSkeleton height="64px" />
                    <AppSkeleton height="64px" />
                </div>

                <div v-else-if="store.rows.length" class="request-list">
                    <div v-for="request in store.rows" :key="request.id" class="request-row">
                        <div class="request-row__meta">
                            <span class="request-row__user">{{ request.user.name }} ({{ request.user.email }})</span>
                            <span class="request-row__detail">
                                Requesting {{ request.requested_roles.join(', ') }} — requested by
                                {{ request.requested_by.name }} on {{ formatDate(request.created_at) }}
                            </span>
                        </div>
                        <div class="request-row__actions">
                            <v-btn variant="tonal" size="small" color="error" @click="rejectTarget = request">
                                Reject
                            </v-btn>
                            <v-btn variant="tonal" size="small" color="primary" @click="approveTarget = request">
                                Approve
                            </v-btn>
                        </div>
                    </div>
                </div>

                <AppEmptyState
                    v-else
                    title="No pending requests"
                    text="Every role elevation has been decided."
                    icon="mdi-shield-check-outline"
                />
            </AppSectionCard>
        </div>

        <ConfirmDialog
            :model-value="Boolean(approveTarget)"
            title="Approve this role elevation?"
            :text="`${approveTarget?.user?.name ?? ''} will be granted ${approveTarget?.requested_roles?.join(', ') ?? ''} immediately.`"
            confirm-label="Approve"
            confirm-color="primary"
            :loading="deciding"
            @update:model-value="approveTarget = null"
            @cancel="approveTarget = null"
            @confirm="confirmApprove"
        />

        <ConfirmDialog
            :model-value="Boolean(rejectTarget)"
            title="Reject this role elevation?"
            :text="`${rejectTarget?.user?.name ?? ''} will keep their current roles.`"
            confirm-label="Reject"
            confirm-color="error"
            :loading="deciding"
            @update:model-value="rejectTarget = null"
            @cancel="rejectTarget = null"
            @confirm="confirmReject"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Governance",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { onMounted, ref } from 'vue';

import AppEmptyState from '../../components/AppEmptyState.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSkeleton from '../../components/AppSkeleton.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import { useAppErrorsStore } from '../../stores/app-errors';
import { useAdminGovernanceStore } from '../../stores/admin-governance';

const store = useAdminGovernanceStore();

const approveTarget = ref(null);
const rejectTarget = ref(null);
const deciding = ref(false);

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

const confirmApprove = async () => {
    if (!approveTarget.value) {
        return;
    }

    deciding.value = true;
    const result = await store.approve(approveTarget.value.id);
    deciding.value = false;
    approveTarget.value = null;

    if (result.ok) {
        await store.fetch();
    } else {
        useAppErrorsStore().show({ message: result.message });
    }
};

const confirmReject = async () => {
    if (!rejectTarget.value) {
        return;
    }

    deciding.value = true;
    const result = await store.reject(rejectTarget.value.id);
    deciding.value = false;
    rejectTarget.value = null;

    if (result.ok) {
        await store.fetch();
    } else {
        useAppErrorsStore().show({ message: result.message });
    }
};

onMounted(() => store.fetch());
</script>

<style scoped>
.page-wrap {
    display: grid;
    gap: 1.5rem;
}

.request-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.request-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.875rem;
    padding: 0.9rem 0.25rem;
    border-bottom: 1px solid var(--rw-border);
}

.request-row:last-child {
    border-bottom: none;
}

.request-row__meta {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.request-row__user {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rw-ink);
}

.request-row__detail {
    font-size: 0.8rem;
    color: var(--rw-muted);
}

.request-row__actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}
</style>
