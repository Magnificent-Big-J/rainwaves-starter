<template>
    <div class="admin-users-page">
        <div class="page-wrap">
            <AppPageHeader
                eyebrow="Administration"
                title="Users"
                subtitle="Manage starter accounts, assign roles, and shape access before application-specific modules are added."
            >
                <template #metrics>
                    <AppStatusBadge status="active" :label="`${store.meta.total} seeded users`" />
                </template>

                <template #actions>
                    <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openCreate"> New user </v-btn>
                </template>
            </AppPageHeader>

            <AppBanner
                title="Starter account baseline"
                message="This page now follows the component-catalog style too: stat cards, sectional surfaces, shared form controls, and reusable table patterns instead of one-off admin markup."
                type="info"
            />

            <div class="admin-users__stats">
                <AppStatCard
                    label="Total users"
                    :value="String(store.meta.total)"
                    helper="Seeded and custom accounts"
                    icon="mdi-account-group-outline"
                    status="active"
                />
                <AppStatCard
                    label="Starter roles"
                    :value="String(store.options.roles.length)"
                    helper="Assignable authorization groups"
                    icon="mdi-shield-account-outline"
                    status="processing"
                />
                <AppStatCard
                    label="Search state"
                    :value="filters.search ? 'Filtered' : 'All users'"
                    helper="Live filter bar is active"
                    icon="mdi-filter-variant"
                    status="active"
                />
            </div>

            <AppSectionCard
                title="User directory"
                subtitle="Filter starter accounts and update role assignments from the reusable admin table surface."
            >
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search users"
                        prepend-inner-icon="mdi-magnify"
                        class="admin-users__search"
                        @update:model-value="onSearch"
                    />
                    <AppSelect
                        v-model="filters.role"
                        :items="[
                            { title: 'All roles', value: '' },
                            ...store.options.roles.map((r) => ({ title: r, value: r })),
                        ]"
                        label="Filter by role"
                        class="admin-users__role-filter"
                        @update:model-value="onFilterChange"
                    />
                    <AppSelect
                        v-model="filters.status"
                        :items="[
                            { title: 'Active', value: '' },
                            { title: 'Archived', value: 'archived' },
                            { title: 'All', value: 'all' },
                        ]"
                        label="Status"
                        class="admin-users__status-filter"
                        @update:model-value="onFilterChange"
                    />
                </AppFilterBar>

                <AppDataTable
                    ref="table"
                    title="All users"
                    :columns="columns"
                    :rows="store.rows"
                    :meta="store.meta"
                    :loading="store.loading"
                    empty-title="No users found"
                    empty-text="Seed starter accounts or create the first admin user from this table."
                    selectable
                    :selected="selected"
                    :sort-by="filters.sortBy"
                    :sort-direction="filters.sortDirection"
                    @update:selected="selected = $event"
                    @sort="onSort"
                    @page-change="onPage"
                    @row-click="openEdit"
                >
                    <template #bulk-actions="{ selected: selectedIds, clear }">
                        <v-btn
                            variant="tonal"
                            size="small"
                            color="error"
                            prepend-icon="mdi-archive-arrow-down-outline"
                            @click="confirmBulkArchive(selectedIds, clear)"
                        >
                            Archive selected
                        </v-btn>
                    </template>

                    <template #row="{ row }">
                        <td>
                            <div class="user-cell">
                                <v-avatar size="34" color="primary" variant="tonal">
                                    <span class="user-cell__initials">{{ initials(row.name) }}</span>
                                </v-avatar>
                                <div>
                                    <div class="user-cell__name">
                                        {{ row.name }}
                                        <AppStatusBadge v-if="row.archived_at" status="inactive" label="Archived" />
                                    </div>
                                    <div class="user-cell__email">{{ row.email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="role-chips">
                                <AppStatusBadge v-for="role in row.roles" :key="role" :status="role" :label="role" />
                                <span v-if="!row.roles?.length" class="text-muted">—</span>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted text-sm">{{ formatDate(row.created_at) }}</span>
                        </td>
                        <td class="text-right">
                            <v-btn
                                v-if="row.archived_at"
                                icon="mdi-backup-restore"
                                size="small"
                                variant="text"
                                title="Restore"
                                @click.stop="restoreUser(row)"
                            />
                            <template v-else>
                                <v-btn
                                    icon="mdi-pencil-outline"
                                    size="small"
                                    variant="text"
                                    @click.stop="openEdit(row)"
                                />
                                <v-btn
                                    icon="mdi-archive-arrow-down-outline"
                                    size="small"
                                    variant="text"
                                    title="Archive"
                                    @click.stop="archiveTarget = row"
                                />
                            </template>
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>
        </div>

        <AppModal
            v-model="dialog.open"
            :title="dialog.mode === 'create' ? 'New user' : 'Edit user'"
            subtitle="Create and update starter accounts with reusable form primitives."
            persistent
        >
            <div class="dialog-form">
                <template v-if="store.loading && dialog.mode === 'edit' && !dialog.form.name">
                    <AppSkeleton height="2.8rem" />
                    <AppSkeleton height="2.8rem" />
                    <AppSkeleton height="2.8rem" />
                </template>

                <template v-else>
                    <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />

                    <v-form @submit.prevent="submitDialog">
                        <v-row dense>
                            <v-col cols="12">
                                <AppTextField
                                    v-model="dialog.form.name"
                                    label="Name"
                                    :error-messages="dialog.errors.name"
                                    autocomplete="name"
                                />
                            </v-col>
                            <v-col cols="12">
                                <AppTextField
                                    v-model="dialog.form.email"
                                    label="Email"
                                    type="email"
                                    :error-messages="dialog.errors.email"
                                    autocomplete="email"
                                />
                            </v-col>
                            <v-col cols="12">
                                <AppTextField
                                    v-model="dialog.form.password"
                                    label="Password"
                                    type="password"
                                    :placeholder="dialog.mode === 'edit' ? 'Leave blank to keep current' : ''"
                                    :error-messages="dialog.errors.password"
                                    autocomplete="new-password"
                                />
                            </v-col>
                            <v-col v-if="dialog.mode === 'create'" cols="12">
                                <AppTextField
                                    v-model="dialog.form.password_confirmation"
                                    label="Confirm password"
                                    type="password"
                                    autocomplete="new-password"
                                />
                            </v-col>
                            <v-col cols="12">
                                <AppAutocomplete
                                    v-model="dialog.form.roles"
                                    :items="store.options.roles"
                                    :error-messages="dialog.errors.roles"
                                    label="Roles"
                                    multiple
                                    chips
                                    closable-chips
                                />
                            </v-col>
                        </v-row>
                    </v-form>
                </template>
            </div>

            <template #actions>
                <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                <v-btn color="primary" :loading="store.loading" @click="submitDialog">
                    {{ dialog.mode === 'create' ? 'Create user' : 'Save changes' }}
                </v-btn>
            </template>
        </AppModal>

        <ConfirmDialog
            v-model="confirmSeed.open"
            title="Use seeded starter accounts?"
            text="The starter already includes seeded owner, ops, and customer accounts. Use the Users table to edit them, or continue to create custom accounts for your app."
            confirm-label="Continue"
            cancel-label="Dismiss"
            @confirm="confirmSeed.open = false"
            @cancel="confirmSeed.open = false"
        />

        <ConfirmDialog
            :model-value="Boolean(archiveTarget)"
            title="Archive this user?"
            :text="`${archiveTarget?.name ?? ''} will no longer be able to sign in. You can restore them at any time from the Archived filter.`"
            confirm-label="Archive"
            confirm-color="error"
            :loading="archiving"
            @update:model-value="archiveTarget = null"
            @cancel="archiveTarget = null"
            @confirm="confirmArchive"
        />

        <ConfirmDialog
            v-model="bulkArchiveDialog.open"
            title="Archive selected users?"
            :text="`${bulkArchiveDialog.ids.length} user(s) will no longer be able to sign in.`"
            confirm-label="Archive"
            confirm-color="error"
            :loading="archiving"
            @confirm="runBulkArchive"
            @cancel="bulkArchiveDialog.open = false"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Users",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { onMounted, reactive, ref } from 'vue';

import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppBanner from '../../components/AppBanner.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppSkeleton from '../../components/AppSkeleton.vue';
import AppTextField from '../../components/AppTextField.vue';
import { useAdminUsersStore } from '../../stores/admin-users';
import { useAppErrorsStore } from '../../stores/app-errors';
import { normalizeErrorMessage } from '../../stores/auth-shared';

const store = useAdminUsersStore();

const columns = [
    { key: 'user', label: 'User', sortable: true, sortKey: 'name' },
    { key: 'roles', label: 'Roles' },
    { key: 'created_at', label: 'Joined', sortable: true },
    { key: 'actions', label: '', class: 'text-right' },
];

const filters = reactive({ search: '', role: '', status: '', page: 1, sortBy: '', sortDirection: 'asc' });
const selected = ref([]);
const archiveTarget = ref(null);
const archiving = ref(false);
const bulkArchiveDialog = reactive({ open: false, ids: [], clear: null });
const table = ref(null);

const dialog = reactive({
    open: false,
    mode: 'create',
    editId: null,
    form: { name: '', email: '', password: '', password_confirmation: '', roles: [] },
    errors: {},
    message: '',
    messageType: 'error',
});

const confirmSeed = reactive({
    open: false,
});

const load = () =>
    store.fetch({
        page: filters.page,
        search: filters.search,
        role: filters.role,
        status: filters.status,
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

const onFilterChange = () => {
    filters.page = 1;
    selected.value = [];
    load();
};

const onSort = ({ sortBy, sortDirection }) => {
    filters.sortBy = sortBy;
    filters.sortDirection = sortDirection;
    load();
};

const initials = (name) =>
    (name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() || '')
        .join('');

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '—';

const openCreate = () => {
    confirmSeed.open = store.meta.total > 0;

    Object.assign(dialog, {
        open: true,
        mode: 'create',
        editId: null,
        form: { name: '', email: '', password: '', password_confirmation: '', roles: [] },
        errors: {},
        message: '',
    });
};

const openEdit = (row) => {
    Object.assign(dialog, {
        open: true,
        mode: 'edit',
        editId: row.id,
        form: {
            name: row.name,
            email: row.email,
            password: '',
            password_confirmation: '',
            roles: [...(row.roles || [])],
        },
        errors: {},
        message: '',
    });
};

const closeDialog = () => {
    dialog.open = false;
};

const submitDialog = async () => {
    dialog.errors = {};
    dialog.message = '';

    try {
        if (dialog.mode === 'create') {
            await store.create(dialog.form);
        } else {
            await store.update(dialog.editId, dialog.form);
        }

        closeDialog();
        await load();
    } catch (error) {
        const data = error?.data;

        dialog.errors = data?.errors ?? {};
        dialog.message = data?.message || 'Something went wrong.';
        dialog.messageType = 'error';
    }
};

const confirmArchive = async () => {
    if (!archiveTarget.value) {
        return;
    }

    archiving.value = true;

    try {
        await store.archive(archiveTarget.value.id);
        archiveTarget.value = null;
        await load();
    } catch (error) {
        useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to archive that user.') });
    } finally {
        archiving.value = false;
    }
};

const restoreUser = async (row) => {
    try {
        await store.restore(row.id);
        await load();
    } catch (error) {
        useAppErrorsStore().show({ message: normalizeErrorMessage(error, 'Unable to restore that user.') });
    }
};

const confirmBulkArchive = (ids, clear) => {
    bulkArchiveDialog.open = true;
    bulkArchiveDialog.ids = ids;
    bulkArchiveDialog.clear = clear;
};

const runBulkArchive = async () => {
    archiving.value = true;

    try {
        const { succeeded, failed } = await store.bulkArchive(bulkArchiveDialog.ids);

        if (failed > 0) {
            useAppErrorsStore().show({
                message: `${succeeded} user(s) archived, ${failed} could not be archived (e.g. the last super-admin).`,
            });
        }

        bulkArchiveDialog.clear?.();
        bulkArchiveDialog.open = false;
        await load();
    } finally {
        archiving.value = false;
    }
};

onMounted(load);
</script>

<style scoped>
.admin-users-page {
    padding: 2rem 1.25rem 4rem;
}

.page-wrap {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 1.5rem;
}

.admin-users__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.9rem;
}

.admin-users__role-filter,
.admin-users__status-filter {
    min-width: 180px;
}

.text-right {
    text-align: right;
}

.admin-users__search {
    min-width: min(360px, 100%);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.35rem 0;
}

.user-cell__initials {
    font-size: 0.85rem;
    font-weight: 700;
}

.user-cell__name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.user-cell__email {
    font-size: 0.8rem;
    color: var(--rw-muted);
}

.role-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.text-muted {
    color: var(--rw-muted);
}

.text-sm {
    font-size: 0.85rem;
}

.dialog-form {
    display: grid;
    gap: 0.5rem;
}

@media (max-width: 960px) {
    .admin-users-page {
        padding: 1.75rem 1rem 3rem;
    }

    .admin-users__stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .admin-users-page {
        padding-inline: 0.75rem;
    }
}
</style>
