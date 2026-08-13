<template>
    <div class="admin-roles-page">
        <AppPageHeader
            eyebrow="Administration"
            title="Roles & Permissions"
            subtitle="Review each role's permission set and adjust it. The super-admin role is fixed to guarantee at least one account can always administer the app."
        >
            <template #metrics>
                <AppStatusBadge status="active" :label="`${store.rows.length} roles`" />
            </template>
        </AppPageHeader>

        <AppSectionCard title="Roles">
            <AppDataTable
                table-id="admin-roles"
                title="All roles"
                :columns="columns"
                :rows="store.rows"
                :loading="store.loading"
                empty-title="No roles found"
                empty-text="Seed roles via RolesAndPermissionsSeeder."
            >
                <template #row="{ row }">
                    <td data-label="">
                        <div class="role-cell">
                            <span class="role-cell__name">{{ row.name }}</span>
                            <AppStatusBadge v-if="row.is_locked" status="active" label="Locked" />
                        </div>
                    </td>
                    <td data-label="Users">
                        <span class="text-muted">{{ row.users_count }}</span>
                    </td>
                    <td data-label="Permissions">
                        <span class="text-muted"
                            >{{ row.permissions.length }} of {{ store.options.permissions.length }}</span
                        >
                    </td>
                    <td data-label="">
                        <v-btn
                            :icon="row.is_locked ? 'mdi-lock-outline' : 'mdi-pencil-outline'"
                            size="small"
                            variant="text"
                            :disabled="row.is_locked"
                            @click.stop="openEdit(row)"
                        />
                    </td>
                </template>
            </AppDataTable>
        </AppSectionCard>

        <AppModal
            v-model="dialog.open"
            :title="`Edit ${dialog.role?.name ?? ''} permissions`"
            subtitle="Changes take effect immediately for every user assigned this role."
            persistent
        >
            <div class="dialog-form">
                <FormStatusAlert :message="dialog.message" type="error" />

                <AppAutocomplete
                    v-model="dialog.permissions"
                    :items="store.options.permissions"
                    label="Permissions"
                    multiple
                    chips
                    closable-chips
                />
            </div>

            <template #actions>
                <v-btn variant="text" @click="dialog.open = false">Cancel</v-btn>
                <v-btn color="primary" :loading="saving" @click="save">Save changes</v-btn>
            </template>
        </AppModal>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Roles & Permissions",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { onMounted, reactive, ref } from 'vue';

import AppAutocomplete from '../../components/AppAutocomplete.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import { useAdminRolesStore } from '../../stores/admin-roles';

const store = useAdminRolesStore();
const saving = ref(false);

const columns = [
    { key: 'name', label: 'Role' },
    { key: 'users', label: 'Users', hideable: true },
    { key: 'permissions', label: 'Permissions', hideable: true },
    { key: 'actions', label: '', class: 'text-right' },
];

const dialog = reactive({ open: false, role: null, permissions: [], message: null });

const openEdit = (role) => {
    dialog.role = role;
    dialog.permissions = [...role.permissions];
    dialog.message = null;
    dialog.open = true;
};

const save = async () => {
    saving.value = true;
    dialog.message = null;

    const result = await store.updatePermissions(dialog.role.id, dialog.permissions);

    saving.value = false;

    if (result.ok) {
        dialog.open = false;
    } else {
        dialog.message = result.message;
    }
};

onMounted(() => store.fetch());
</script>

<style scoped>
.admin-roles-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.role-cell {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
}

.dialog-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.text-right {
    text-align: right;
}

.text-muted {
    color: var(--rw-muted);
}
</style>
