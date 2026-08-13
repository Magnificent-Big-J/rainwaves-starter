<template>
    <div class="audit-log-page">
        <AppPageHeader
            eyebrow="Administration"
            title="Audit Log"
            subtitle="Security and account activity recorded across the app, including the 14 lara-auth-suite security events (see CLAUDE.md)."
        />

        <AppSectionCard title="Activity">
            <AppFilterBar>
                <AppTextField
                    v-model="filters.search"
                    label="Search description"
                    prepend-inner-icon="mdi-magnify"
                    @update:model-value="onFilterChange"
                />
                <AppSelect
                    v-model="filters.logName"
                    :items="[
                        { title: 'All logs', value: '' },
                        ...store.options.log_names.map((n) => ({ title: n, value: n })),
                    ]"
                    label="Filter by log"
                    @update:model-value="onFilterChange"
                />
            </AppFilterBar>

            <AppDataTable
                title="Recent activity"
                :columns="columns"
                :rows="store.rows"
                :meta="store.pagination"
                :loading="store.loading"
                empty-title="No activity recorded"
                empty-text="Security and account events will appear here as they happen."
                @page-change="onPage"
            >
                <template #row="{ row }">
                    <td>
                        <div class="activity-cell">
                            <span class="activity-cell__description">{{ row.description }}</span>
                            <AppStatusBadge v-if="row.log_name" status="processing" :label="row.log_name" />
                        </div>
                    </td>
                    <td>
                        <span class="text-muted">{{ row.causer?.name ?? 'System' }}</span>
                    </td>
                    <td>
                        <span class="text-muted text-sm">{{ formatDate(row.created_at) }}</span>
                    </td>
                </template>
            </AppDataTable>
        </AppSectionCard>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Audit Log",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { onMounted, reactive } from 'vue';

import AppDataTable from '../../components/AppDataTable.vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSelect from '../../components/AppSelect.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useAdminActivityLogStore } from '../../stores/admin-activity-log';

const store = useAdminActivityLogStore();

const columns = [
    { key: 'description', label: 'Event' },
    { key: 'causer', label: 'By' },
    { key: 'created_at', label: 'When' },
];

const filters = reactive({ search: '', logName: '' });

let filterTimer = null;
const onFilterChange = () => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => store.fetch({ search: filters.search, logName: filters.logName }), 300);
};

const onPage = (page) => store.fetch({ page, search: filters.search, logName: filters.logName });

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString() : '—');

onMounted(() => store.fetch());
</script>

<style scoped>
.audit-log-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.activity-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.text-muted {
    color: var(--rw-muted);
}

.text-sm {
    font-size: 0.82rem;
}
</style>
