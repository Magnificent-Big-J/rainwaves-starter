<template>
    <div class="admin-teams-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Administration"
                title="Teams"
                subtitle="Read-only visibility into every team on the platform — support and ops context, not a management surface. Teams manage themselves from Account &rarr; Team."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} teams`" />
                </template>
            </AppPageHeader>

            <AppSectionCard title="Team directory" subtitle="Search across every team, regardless of who owns it.">
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search teams"
                        prepend-inner-icon="mdi-magnify"
                        @update:model-value="onSearch"
                    />
                </AppFilterBar>

                <AppDataTable
                    table-id="admin-teams"
                    title="All teams"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No teams found"
                    empty-text="No team has been created on the platform yet."
                    clickable-rows
                    :sort-by="filters.sortBy"
                    :sort-direction="filters.sortDirection"
                    @sort="onSort"
                    @page-change="onPage"
                    @row-click="openDetail"
                >
                    <template #row="{ row }">
                        <td data-label="">
                            <div class="team-cell__name">{{ row.name }}</div>
                            <div class="team-cell__slug">{{ row.slug }}</div>
                        </td>
                        <td data-label="Owner">
                            <div class="team-cell__owner">{{ row.owner.name }}</div>
                            <div class="team-cell__owner-email">{{ row.owner.email }}</div>
                        </td>
                        <td data-label="Members">{{ row.member_count }} / {{ row.max_members }}</td>
                        <td data-label="Created">
                            <span class="text-muted text-sm">{{ formatDate(row.created_at) }}</span>
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppDrawer
            :model-value="Boolean(activeTeamId)"
            :title="store.detail?.team?.name ?? 'Team'"
            subtitle="Overview and current members."
            @update:model-value="activeTeamId = null"
        >
            <template v-if="store.detailLoading">
                <AppSkeleton height="2.2rem" />
                <AppSkeleton height="2.2rem" />
                <AppSkeleton height="2.2rem" />
            </template>

            <template v-else-if="store.detail">
                <div class="detail-stats">
                    <AppStatCard
                        label="Members"
                        :value="`${store.detail.team.member_count} / ${store.detail.team.max_members}`"
                        helper="Current usage against the plan cap"
                        icon="mdi-account-group-outline"
                        status="active"
                    />
                    <AppStatCard
                        label="Owner"
                        :value="store.detail.team.owner.name"
                        :helper="store.detail.team.owner.email"
                        icon="mdi-account-star-outline"
                        status="processing"
                    />
                </div>

                <div class="detail-members">
                    <h4 class="detail-members__title">Members</h4>
                    <ul class="detail-members__list">
                        <li v-for="member in store.detail.members" :key="member.id" class="detail-members__row">
                            <div>
                                <div class="detail-members__name">{{ member.name }}</div>
                                <div class="detail-members__email">{{ member.email }}</div>
                            </div>
                            <AppStatusBadge
                                :status="member.is_owner ? 'active' : 'processing'"
                                :label="member.role_label"
                            />
                        </li>
                    </ul>
                </div>
            </template>
        </AppDrawer>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Teams",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { onMounted, reactive, ref } from 'vue';

import AppDataTable from '../../components/AppDataTable.vue';
import AppDrawer from '../../components/AppDrawer.vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSkeleton from '../../components/AppSkeleton.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import AppTextField from '../../components/AppTextField.vue';
import { usePersistedFilters } from '../../composables/usePersistedFilters';
import { useAdminTeamsStore } from '../../stores/admin-teams';

const store = useAdminTeamsStore();

const columns = [
    { key: 'team', label: 'Team', sortable: true, sortKey: 'name' },
    { key: 'owner', label: 'Owner' },
    { key: 'members', label: 'Members' },
    { key: 'created_at', label: 'Created', sortable: true, hideable: true },
];

const filters = reactive({ search: '', page: 1, sortBy: '', sortDirection: 'asc' });
usePersistedFilters('admin-teams', filters, { exclude: ['page'] });
const activeTeamId = ref(null);

const load = () =>
    store.fetch({
        page: filters.page,
        search: filters.search,
        sortBy: filters.sortBy,
        sortDirection: filters.sortDirection,
    });

const onSearch = (val) => {
    filters.search = val;
    filters.page = 1;
    load();
};

const onPage = (page) => {
    filters.page = page;
    load();
};

const onSort = ({ sortBy, sortDirection }) => {
    filters.sortBy = sortBy;
    filters.sortDirection = sortDirection;
    load();
};

const openDetail = (row) => {
    activeTeamId.value = row.id;
    store.fetchDetail(row.id);
};

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

onMounted(load);
</script>

<style scoped>
.page-wrap {
    display: grid;
    gap: 1.5rem;
}

.team-cell__name {
    font-weight: 600;
    font-size: 0.9rem;
}

.team-cell__slug {
    font-size: 0.8rem;
    color: var(--rw-muted);
}

.team-cell__owner {
    font-size: 0.9rem;
}

.team-cell__owner-email {
    font-size: 0.8rem;
    color: var(--rw-muted);
}

.text-muted {
    color: var(--rw-muted);
}

.text-sm {
    font-size: 0.85rem;
}

.detail-stats {
    display: grid;
    gap: 0.75rem;
}

.detail-members__title {
    margin: 0 0 0.5rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--rw-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.detail-members__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.6rem;
}

.detail-members__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.6rem 0.75rem;
    border-radius: 10px;
    background: var(--rw-surface-muted, rgba(17, 34, 51, 0.03));
}

.detail-members__name {
    font-weight: 600;
    font-size: 0.88rem;
}

.detail-members__email {
    font-size: 0.78rem;
    color: var(--rw-muted);
}
</style>
