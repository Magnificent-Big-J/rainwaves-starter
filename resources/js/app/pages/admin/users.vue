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
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-account-plus"
                        @click="openCreate"
                    >
                        New user
                    </v-btn>
                </template>
            </AppPageHeader>

            <AppDataTable
                title="All users"
                :columns="columns"
                :rows="store.rows"
                :meta="store.meta"
                :loading="store.loading"
                searchable
                empty-title="No users found"
                empty-text="Seed starter accounts or create the first admin user from this table."
                @search="onSearch"
                @page-change="onPage"
                @row-click="openEdit"
            >
                <template #toolbar>
                    <AppSelect
                        v-model="filters.role"
                        :items="[{ title: 'All roles', value: '' }, ...store.options.roles.map(r => ({ title: r, value: r }))]"
                        label="Filter by role"
                        class="admin-users__role-filter"
                        @update:model-value="onRoleFilter"
                    />
                </template>

                <template #row="{ row }">
                    <td>
                        <div class="user-cell">
                            <v-avatar size="34" color="primary" variant="tonal">
                                <span class="user-cell__initials">{{ initials(row.name) }}</span>
                            </v-avatar>
                            <div>
                                <div class="user-cell__name">{{ row.name }}</div>
                                <div class="user-cell__email">{{ row.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="role-chips">
                            <AppStatusBadge
                                v-for="role in row.roles"
                                :key="role"
                                :status="role"
                                :label="role"
                            />
                            <span v-if="!row.roles?.length" class="text-muted">—</span>
                        </div>
                    </td>
                    <td>
                        <span class="text-muted text-sm">{{ formatDate(row.created_at) }}</span>
                    </td>
                    <td>
                        <v-btn
                            icon="mdi-pencil-outline"
                            size="small"
                            variant="text"
                            @click.stop="openEdit(row)"
                        />
                    </td>
                </template>
            </AppDataTable>
        </div>

        <v-dialog v-model="dialog.open" max-width="520" persistent>
            <v-card class="dialog-card">
                <v-card-title class="dialog-card__title">
                    {{ dialog.mode === 'create' ? 'New user' : 'Edit user' }}
                </v-card-title>

                <v-card-text class="dialog-form">
                    <FormStatusAlert :message="dialog.message" :type="dialog.messageType" />

                    <v-form @submit.prevent="submitDialog">
                        <v-row dense>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="dialog.form.name"
                                    label="Name"
                                    :error-messages="dialog.errors.name"
                                    autocomplete="name"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="dialog.form.email"
                                    label="Email"
                                    type="email"
                                    :error-messages="dialog.errors.email"
                                    autocomplete="email"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="dialog.form.password"
                                    label="Password"
                                    type="password"
                                    :placeholder="dialog.mode === 'edit' ? 'Leave blank to keep current' : ''"
                                    :error-messages="dialog.errors.password"
                                    autocomplete="new-password"
                                />
                            </v-col>
                            <v-col v-if="dialog.mode === 'create'" cols="12">
                                <v-text-field
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

                        <div class="dialog-actions">
                            <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                            <v-btn
                                type="submit"
                                color="primary"
                                :loading="store.loading"
                            >
                                {{ dialog.mode === 'create' ? 'Create user' : 'Save changes' }}
                            </v-btn>
                        </div>
                    </v-form>
                </v-card-text>
            </v-card>
        </v-dialog>

        <ConfirmDialog
            v-model="confirmSeed.open"
            title="Use seeded starter accounts?"
            text="The starter already includes seeded owner, ops, and customer accounts. Use the Users table to edit them, or continue to create custom accounts for your app."
            confirm-label="Continue"
            cancel-label="Dismiss"
            @confirm="confirmSeed.open = false"
            @cancel="confirmSeed.open = false"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Users",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { onMounted, reactive } from 'vue';

import { useAdminUsersStore } from '../../stores/admin-users';

const store = useAdminUsersStore();

const columns = [
    { key: 'user', label: 'User' },
    { key: 'roles', label: 'Roles' },
    { key: 'created_at', label: 'Joined' },
    { key: 'actions', label: '', class: 'text-right' },
];

const filters = reactive({ search: '', role: '', page: 1 });

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
    store.fetch({ page: filters.page, search: filters.search, role: filters.role });

const onSearch = (val) => {
    filters.search = val;
    filters.page = 1;
    load();
};

const onPage = (page) => {
    filters.page = page;
    load();
};

const onRoleFilter = () => {
    filters.page = 1;
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
        form: { name: row.name, email: row.email, password: '', password_confirmation: '', roles: [...(row.roles || [])] },
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

.admin-users__role-filter {
    min-width: 220px;
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
    font-weight: 600;
    font-size: 0.9rem;
}

.user-cell__email {
    font-size: 0.8rem;
    color: var(--starter-muted);
}

.role-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.text-muted {
    color: var(--starter-muted);
}

.text-sm {
    font-size: 0.85rem;
}

.dialog-card {
    background: rgba(255, 253, 248, 0.98);
    border: 1px solid rgba(17, 34, 51, 0.08);
}

.dialog-card__title {
    font-size: 1.1rem;
    font-weight: 700;
    padding-bottom: 0.5rem;
}

.dialog-form {
    display: grid;
    gap: 0.5rem;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.75rem;
}
</style>
