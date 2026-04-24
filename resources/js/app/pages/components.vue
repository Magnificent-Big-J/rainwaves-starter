<template>
    <div class="catalog-page">
        <AppPageHeader
            eyebrow="Commercial starter"
            title="Component catalog"
            subtitle="A live index of the shared Rainwaves starter components, grouped by purpose so teams can see what ships before they build new UI."
        >
            <template #metrics>
                <AppStatusBadge status="active" :label="`${componentCount} components`" />
                <AppStatusBadge status="processing" :label="`${groups.length} categories`" />
            </template>
        </AppPageHeader>

        <div class="catalog-stats">
            <AppStatCard
                label="Form layer"
                :value="String(formComponents.length)"
                helper="Shared inputs, actions, and state helpers"
                icon="mdi-form-select"
                status="active"
            />
            <AppStatCard
                label="Feedback layer"
                :value="String(feedbackComponents.length)"
                helper="Notifications, alerts, modal, drawer, loading states"
                icon="mdi-bell-badge-outline"
                status="processing"
            />
            <AppStatCard
                label="Billing & security"
                :value="String(domainComponents.length)"
                helper="Starter-specific domain building blocks"
                icon="mdi-shield-lock-outline"
                status="active"
            />
            <AppStatCard
                label="Data & widgets"
                :value="String(dataComponents.length)"
                helper="Tables, charts, cards, timelines, status surfaces"
                icon="mdi-chart-box-outline"
                status="active"
            />
        </div>

        <AppBanner
            title="Shared-first rule"
            message="If a new screen needs one of these patterns, consume the shared component first. Only add a new component when the use case cannot be expressed cleanly by the existing catalog."
            type="info"
        />

        <div class="catalog-grid">
            <AppSectionCard
                v-for="group in groups"
                :key="group.title"
                :title="group.title"
                :subtitle="group.subtitle"
            >
                <div class="catalog-badges">
                    <AppStatusBadge
                        v-for="item in group.items"
                        :key="item"
                        status="draft"
                        :label="item"
                    />
                </div>
            </AppSectionCard>
        </div>

        <div class="catalog-playground">
            <AppSectionCard title="Playground" subtitle="Representative live examples from the catalog.">
                <div class="playground-grid">
                    <div class="playground-stack">
                        <AppTextField label="Starter text field" placeholder="Shared input wrapper" />
                        <AppTextarea label="Starter textarea" placeholder="Long-form shared input" />
                        <AppSelect
                            label="Starter select"
                            :items="['Admin', 'Staff', 'Customer']"
                            model-value="Customer"
                        />
                        <AppAutocomplete
                            label="Starter autocomplete"
                            :items="['PayFast', 'Sanctum', 'Horizon', 'Media Library']"
                            model-value="PayFast"
                        />
                        <AppAddressAutocomplete
                            v-model="demoAddress"
                            v-model:query-value="demoAddressQuery"
                            label="Starter address autocomplete"
                            country="za"
                        />
                        <AppFileInput label="Starter file input" hint="Shared upload field surface" />
                    </div>

                    <div class="playground-stack">
                        <AppAlert
                            title="Inline alert"
                            message="Use this for section-level or form-level feedback."
                            type="warning"
                        />
                        <AppEmptyState
                            title="No component selected"
                            text="Use the catalog to review what already exists before building a new primitive."
                        />
                        <div class="playground-actions">
                            <v-btn color="primary" @click="demoModal = true">Open modal</v-btn>
                            <v-btn variant="outlined" @click="demoDrawer = true">Open drawer</v-btn>
                        </div>
                    </div>
                </div>
            </AppSectionCard>

            <div class="playground-side">
                <AppLineChart
                    title="Starter chart wrapper"
                    subtitle="A shared widget surface for dashboards and reporting."
                    :categories="chartCategories"
                    :series="chartSeries"
                />

                <PaymentEventList
                    title="Starter timeline surface"
                    subtitle="Domain components stay visible in the same catalog."
                    :events="events"
                />
            </div>
        </div>

        <AppModal v-model="demoModal" title="Catalog modal" subtitle="Shared dialog pattern in action.">
            <div class="playground-stack">
                <AppTextField label="Name" />
                <AppTextarea label="Notes" />
            </div>

            <template #actions>
                <v-btn variant="text" @click="demoModal = false">Cancel</v-btn>
                <v-btn color="primary" @click="demoModal = false">Save</v-btn>
            </template>
        </AppModal>

        <AppDrawer v-model="demoDrawer" title="Catalog drawer" subtitle="Quick side-panel pattern.">
            <AppSkeleton height="2.4rem" />
            <AppSkeleton height="2.4rem" />
            <AppSkeleton height="7rem" />
        </AppDrawer>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "Components",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { computed, ref } from 'vue';

const formComponents = [
    'AppTextField',
    'AppTextarea',
    'AppSelect',
    'AppAutocomplete',
    'AppFileInput',
    'FormActions',
    'FormStatusAlert',
    'AppAddressAutocomplete',
];

const layoutComponents = [
    'AppPageHeader',
    'AppSectionCard',
    'AppEmptyState',
    'AppHeader',
    'AuthCard',
];

const feedbackComponents = [
    'AppAlert',
    'AppBanner',
    'AppToastHost',
    'AppNotificationPanel',
    'AppNotificationItem',
    'ConfirmDialog',
    'AppModal',
    'AppDrawer',
    'AppSkeleton',
    'BusyOverlay',
];

const dataComponents = [
    'AppDataTable',
    'AppPaginationBar',
    'AppStatusBadge',
    'AppStatCard',
    'AppTimeline',
    'AppLineChart',
    'AppDonutChart',
    'AppFilterBar',
];

const domainComponents = [
    'MediaUploader',
    'PaymentStatusCard',
    'SubscriptionStatusCard',
    'PaymentEventList',
    'TwoFactorSetupPanel',
    'RecoveryCodesPanel',
];

const groups = [
    {
        title: 'Forms',
        subtitle: 'Shared data-entry primitives and auth/form helpers.',
        items: formComponents,
    },
    {
        title: 'Layout',
        subtitle: 'Page chrome and structural building blocks.',
        items: layoutComponents,
    },
    {
        title: 'Feedback',
        subtitle: 'Notifications, dialogs, loading states, and attention surfaces.',
        items: feedbackComponents,
    },
    {
        title: 'Data & widgets',
        subtitle: 'Tables, badges, pagination, charts, and timeline patterns.',
        items: dataComponents,
    },
    {
        title: 'Domain surfaces',
        subtitle: 'Starter-specific billing, media, and security components.',
        items: domainComponents,
    },
];

const componentCount = computed(() =>
    groups.reduce((total, group) => total + group.items.length, 0)
);

const chartCategories = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
const chartSeries = [
    { name: 'Adoption', data: [5, 9, 14, 19, 24] },
];

const events = [
    {
        id: 1,
        title: 'Billing widget wired',
        time: 'Current',
        text: 'Payment timeline and status cards are part of the shared catalog.',
        type: 'success',
    },
    {
        id: 2,
        title: 'Security surfaces ready',
        time: 'Current',
        text: 'Two-factor setup and recovery code panels are reusable starter assets.',
        type: 'info',
    },
    {
        id: 3,
        title: 'More expansion ahead',
        time: 'Next',
        text: 'The catalog can keep growing with date/time, tabs, breadcrumbs, and richer inputs.',
        type: 'warning',
    },
];

const demoModal = ref(false);
const demoDrawer = ref(false);
const demoAddress = ref(null);
const demoAddressQuery = ref('');
</script>

<style scoped>
.catalog-page {
    padding: 2rem 1.25rem 4rem;
    display: grid;
    gap: 1.5rem;
}

.catalog-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
}

.catalog-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.catalog-badges {
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.catalog-playground {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 1rem;
    align-items: start;
}

.playground-grid,
.playground-stack,
.playground-side {
    display: grid;
    gap: 1rem;
}

.playground-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

@media (max-width: 960px) {
    .catalog-stats,
    .catalog-grid,
    .catalog-playground {
        grid-template-columns: 1fr;
    }
}
</style>
