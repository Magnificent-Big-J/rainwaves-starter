<template>
    <div class="team-page">
        <AppPageHeader
            eyebrow="Account"
            title="Team"
            :subtitle="
                team.team ? 'Manage your team\'s name, plan, and usage.' : 'Create a team to start inviting people.'
            "
        />

        <AppSectionCard v-if="!team.team" title="Create your team">
            <FormStatusAlert :message="createError" type="error" />

            <v-form class="team-form" @submit.prevent="submitCreate">
                <AppTextField v-model="createName" label="Team name" required />
                <FormActions :loading="creating" submit-label="Create team" />
            </v-form>
        </AppSectionCard>

        <template v-else>
            <div class="team-page__stats">
                <AppStatCard
                    label="Members"
                    :value="String(team.team.member_count)"
                    :helper="`of ${team.team.max_members} on your current plan`"
                    icon="mdi-account-group-outline"
                    status="active"
                />
                <AppStatCard
                    label="Your role"
                    :value="roleLabel"
                    helper="Owner can rename or delete the team"
                    icon="mdi-shield-account-outline"
                    status="active"
                />
            </div>

            <AppSectionCard title="Team settings" subtitle="Owner and admins can rename the team.">
                <FormStatusAlert :message="renameError" type="error" />

                <v-form class="team-form" @submit.prevent="submitRename">
                    <AppTextField v-model="renameName" label="Team name" required :disabled="!canManage" />
                    <FormActions v-if="canManage" :loading="renaming" submit-label="Save changes" />
                </v-form>
            </AppSectionCard>

            <AppSectionCard
                v-if="isOwner"
                title="Danger zone"
                subtitle="Deleting a team removes every member and pending invite."
            >
                <v-btn color="error" variant="tonal" prepend-icon="mdi-delete-outline" @click="confirmDelete = true">
                    Delete team
                </v-btn>
            </AppSectionCard>
        </template>

        <ConfirmDialog
            v-model="confirmDelete"
            title="Delete this team?"
            text="This removes every member and pending invite immediately. This cannot be undone."
            confirm-label="Delete team"
            confirm-color="error"
            :loading="deleting"
            @confirm="submitDelete"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "contextual",
        "title": "Team",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { computed, onMounted, ref } from 'vue';

import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppTextField from '../../components/AppTextField.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormActions from '../../components/FormActions.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import { useTeamStore } from '../../stores/team';

const team = useTeamStore();

const createName = ref('');
const createError = ref('');
const creating = ref(false);

const renameName = ref('');
const renameError = ref('');
const renaming = ref(false);

const confirmDelete = ref(false);
const deleting = ref(false);

const isOwner = computed(() => team.myRole === 'owner');
const canManage = computed(() => team.myRole === 'owner' || team.myRole === 'admin');
const roleLabel = computed(() => (team.myRole ? team.myRole.charAt(0).toUpperCase() + team.myRole.slice(1) : '—'));

const submitCreate = async () => {
    createError.value = '';

    if (!createName.value) {
        createError.value = 'Enter a team name.';

        return;
    }

    creating.value = true;
    const result = await team.create(createName.value);
    creating.value = false;

    if (result.ok) {
        renameName.value = result.team?.name ?? '';
    } else {
        createError.value = result.message;
    }
};

const submitRename = async () => {
    renameError.value = '';

    if (!renameName.value) {
        renameError.value = 'Team name is required.';

        return;
    }

    renaming.value = true;
    const result = await team.rename(renameName.value);
    renaming.value = false;

    if (!result.ok) {
        renameError.value = result.message;
    }
};

const submitDelete = async () => {
    deleting.value = true;
    const result = await team.deleteTeam();
    deleting.value = false;
    confirmDelete.value = false;

    if (!result.ok) {
        renameError.value = result.message;
    }
};

onMounted(async () => {
    await team.fetch();
    renameName.value = team.team?.name ?? '';
});
</script>

<style scoped>
.team-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.team-form {
    display: grid;
    gap: 1.25rem;
    max-width: 420px;
}

.team-page__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
    max-width: 640px;
}

@media (max-width: 600px) {
    .team-page__stats {
        grid-template-columns: 1fr;
    }
}
</style>
