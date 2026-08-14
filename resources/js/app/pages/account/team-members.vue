<template>
    <div class="team-members-page">
        <AppPageHeader
            eyebrow="Account"
            title="Team Members"
            subtitle="Invite people to your team and manage their roles."
        >
            <template #actions>
                <v-btn v-if="canManage" color="primary" prepend-icon="mdi-account-plus" @click="openInvite">
                    Invite member
                </v-btn>
            </template>
        </AppPageHeader>

        <AppEmptyState
            v-if="!team.team"
            title="No team yet"
            text="Create a team on the Team page before inviting members."
            icon="mdi-account-multiple-outline"
        />

        <template v-else>
            <AppSectionCard title="Members">
                <AppFilterBar>
                    <AppTextField
                        v-model="filters.search"
                        label="Search members"
                        prepend-inner-icon="mdi-magnify"
                        @update:model-value="onSearch"
                    />
                </AppFilterBar>

                <AppDataTable
                    table-id="team-members"
                    :columns="columns"
                    :rows="members.rows"
                    :meta="members.meta"
                    :loading="members.loading"
                    empty-title="No members found"
                    empty-text="Invite someone to get started."
                    :sort-by="filters.sortBy"
                    :sort-direction="filters.sortDirection"
                    @sort="onSort"
                    @page-change="onPage"
                >
                    <template #row="{ row }">
                        <td data-label="Member">
                            <div class="member-cell">
                                <span class="member-cell__name">{{ row.name }}</span>
                                <span class="member-cell__email">{{ row.email }}</span>
                            </div>
                        </td>
                        <td data-label="Role">
                            <AppSelect
                                v-if="canManage && !row.is_owner && !isSelf(row)"
                                :model-value="row.role"
                                :items="roleOptions"
                                density="compact"
                                hide-details
                                class="member-role-select"
                                @update:model-value="(role) => changeRole(row, role)"
                            />
                            <AppStatusBadge v-else status="active" :label="row.role_label" />
                        </td>
                        <td data-label="Joined">{{ formatDate(row.joined_at) }}</td>
                        <td data-label="" class="text-right">
                            <v-btn
                                v-if="canManage && !row.is_owner"
                                variant="text"
                                size="small"
                                color="error"
                                @click="removeTarget = row"
                            >
                                {{ isSelf(row) ? 'Leave' : 'Remove' }}
                            </v-btn>
                        </td>
                    </template>
                </AppDataTable>
            </AppSectionCard>

            <AppSectionCard
                v-if="canManage"
                title="Pending invites"
                subtitle="Invitations that haven't been accepted yet."
            >
                <div v-if="members.invitesLoading" class="team-members-page__loading">
                    <AppSkeleton v-for="n in 2" :key="n" height="56px" />
                </div>

                <div v-else-if="members.invites.length" class="invite-list">
                    <div v-for="invite in members.invites" :key="invite.id" class="invite-row">
                        <div class="invite-row__meta">
                            <span class="invite-row__email">{{ invite.email }}</span>
                            <span class="invite-row__detail">
                                {{ invite.role_label }} · invited by {{ invite.invited_by }} · expires
                                {{ formatDate(invite.expires_at) }}
                            </span>
                        </div>
                        <v-btn variant="text" size="small" color="error" @click="revokeTarget = invite"> Revoke </v-btn>
                    </div>
                </div>

                <AppEmptyState
                    v-else
                    title="No pending invites"
                    text="Invite a teammate to see it appear here."
                    icon="mdi-email-outline"
                />
            </AppSectionCard>
        </template>

        <AppModal
            v-model="inviteDialog.open"
            title="Invite a team member"
            subtitle="They'll receive an email with a link to accept."
        >
            <div class="invite-form">
                <FormStatusAlert :message="inviteDialog.message" type="error" />
                <AppTextField
                    v-model="inviteDialog.form.email"
                    label="Email"
                    type="email"
                    :error-messages="inviteDialog.errors.email"
                    autocomplete="email"
                />
                <AppSelect v-model="inviteDialog.form.role" :items="roleOptions" label="Role" />
            </div>

            <template #actions>
                <v-btn variant="text" @click="inviteDialog.open = false">Cancel</v-btn>
                <v-btn color="primary" :loading="inviteDialog.loading" @click="submitInvite">Send invite</v-btn>
            </template>
        </AppModal>

        <ConfirmDialog
            :model-value="Boolean(removeTarget)"
            :title="isSelf(removeTarget) ? 'Leave this team?' : 'Remove this member?'"
            :text="
                isSelf(removeTarget)
                    ? 'You will lose access to this team immediately.'
                    : `${removeTarget?.name ?? ''} will lose access to this team immediately.`
            "
            :confirm-label="isSelf(removeTarget) ? 'Leave' : 'Remove'"
            confirm-color="error"
            :loading="removing"
            @update:model-value="removeTarget = null"
            @cancel="removeTarget = null"
            @confirm="confirmRemove"
        />

        <ConfirmDialog
            :model-value="Boolean(revokeTarget)"
            title="Revoke this invitation?"
            :text="`${revokeTarget?.email ?? ''} will no longer be able to accept this invite.`"
            confirm-label="Revoke"
            confirm-color="error"
            :loading="revoking"
            @update:model-value="revokeTarget = null"
            @cancel="revokeTarget = null"
            @confirm="confirmRevoke"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "contextual",
        "title": "Team Members",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

import AppDataTable from '../../components/AppDataTable.vue';
import AppEmptyState from '../../components/AppEmptyState.vue';
import AppFilterBar from '../../components/AppFilterBar.vue';
import AppModal from '../../components/AppModal.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSelect from '../../components/AppSelect.vue';
import AppSkeleton from '../../components/AppSkeleton.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import AppTextField from '../../components/AppTextField.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import { useAppErrorsStore } from '../../stores/app-errors';
import { useSessionStore } from '../../stores/session';
import { useTeamStore } from '../../stores/team';
import { useTeamMembersStore } from '../../stores/team-members';

const session = useSessionStore();
const team = useTeamStore();
const members = useTeamMembersStore();

const canManage = computed(() => team.myRole === 'owner' || team.myRole === 'admin');
const isSelf = (row) => Boolean(row) && row.user_id === session.user?.id;

const columns = [
    { key: 'member', label: 'Member' },
    { key: 'role', label: 'Role' },
    { key: 'joined', label: 'Joined', hideable: true },
    { key: 'actions', label: '', srLabel: 'Actions', class: 'text-right' },
];

const roleOptions = [
    { title: 'Admin', value: 'admin' },
    { title: 'Member', value: 'member' },
];

const filters = reactive({ search: '', page: 1, sortBy: '', sortDirection: 'asc' });

const removeTarget = ref(null);
const removing = ref(false);
const revokeTarget = ref(null);
const revoking = ref(false);

const inviteDialog = reactive({
    open: false,
    loading: false,
    message: '',
    errors: {},
    form: { email: '', role: 'member' },
});

const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString() : '—');

const load = () => {
    if (!team.team) {
        return;
    }

    members.fetchMembers(team.team.id, {
        page: filters.page,
        search: filters.search,
        sortBy: filters.sortBy,
        sortDirection: filters.sortDirection,
    });

    if (canManage.value) {
        members.fetchInvites(team.team.id);
    }
};

const onSearch = (val) => {
    filters.search = val;
    filters.page = 1;
    load();
};

const onSort = ({ sortBy, sortDirection }) => {
    filters.sortBy = sortBy;
    filters.sortDirection = sortDirection;
    load();
};

const onPage = (page) => {
    filters.page = page;
    load();
};

const changeRole = async (row, role) => {
    const result = await members.updateMemberRole(team.team.id, row.user_id, role);

    if (result.ok) {
        load();
    } else {
        useAppErrorsStore().show({ message: result.message });
    }
};

const confirmRemove = async () => {
    if (!removeTarget.value) {
        return;
    }

    removing.value = true;
    const result = await members.removeMember(team.team.id, removeTarget.value.user_id);
    removing.value = false;
    removeTarget.value = null;

    if (result.ok) {
        load();
        await team.fetch();
    } else {
        useAppErrorsStore().show({ message: result.message });
    }
};

const openInvite = () => {
    inviteDialog.form.email = '';
    inviteDialog.form.role = 'member';
    inviteDialog.errors = {};
    inviteDialog.message = '';
    inviteDialog.open = true;
};

const submitInvite = async () => {
    inviteDialog.loading = true;
    inviteDialog.errors = {};
    inviteDialog.message = '';

    const result = await members.createInvite(team.team.id, inviteDialog.form);

    inviteDialog.loading = false;

    if (result.ok) {
        inviteDialog.open = false;
        members.fetchInvites(team.team.id);
    } else {
        inviteDialog.message = result.message;
    }
};

const confirmRevoke = async () => {
    if (!revokeTarget.value) {
        return;
    }

    revoking.value = true;
    const result = await members.revokeInvite(team.team.id, revokeTarget.value.id);
    revoking.value = false;
    revokeTarget.value = null;

    if (result.ok) {
        members.fetchInvites(team.team.id);
    } else {
        useAppErrorsStore().show({ message: result.message });
    }
};

onMounted(async () => {
    if (!team.loaded) {
        await team.fetch();
    }

    load();
});
</script>

<style scoped>
.team-members-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.member-cell {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.member-cell__name {
    font-weight: 600;
    color: var(--rw-ink);
}

.member-cell__email {
    font-size: 0.8rem;
    color: var(--rw-muted);
}

.member-role-select {
    max-width: 160px;
}

.invite-list {
    display: flex;
    flex-direction: column;
}

.invite-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.875rem;
    padding: 0.9rem 0.25rem;
    border-bottom: 1px solid var(--rw-border);
}

.invite-row:last-child {
    border-bottom: none;
}

.invite-row__meta {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.invite-row__email {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rw-ink);
}

.invite-row__detail {
    font-size: 0.8rem;
    color: var(--rw-muted);
}

.invite-form {
    display: grid;
    gap: 1.1rem;
}

.team-members-page__loading {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
}
</style>
