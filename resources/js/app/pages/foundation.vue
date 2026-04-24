<template>
    <div class="foundation-page">
        <div class="foundation-page__wrap">
            <AppPageHeader
                eyebrow="Commercial kit"
                title="Starter UI system"
                subtitle="The starter now carries a real shared component layer for pages, forms, data states, pagination, statuses, charts, and widgets."
            >
                <template #metrics>
                    <AppStatusBadge status="active" label="Phase 1 live" />
                    <AppStatusBadge status="processing" label="Widget build-out" />
                </template>
            </AppPageHeader>

            <AppBanner
                title="Notification system now included"
                message="The starter now has toast notifications, inline banners, alerts, and a topbar notification center pattern ready for system, billing, and security events."
                type="info"
            />

            <div class="foundation-stats">
                <AppStatCard
                    label="Shared components"
                    value="10"
                    helper="First commercial batch landed"
                    icon="mdi-toy-brick-plus-outline"
                />
                <AppStatCard
                    label="Chart widgets"
                    value="2"
                    helper="Line and donut chart wrappers"
                    icon="mdi-chart-donut"
                    status="active"
                />
                <AppStatCard
                    label="Form wrappers"
                    value="2"
                    helper="Autocomplete and select primitives"
                    icon="mdi-form-select"
                />
                <AppStatCard
                    label="Ready modules"
                    value="3"
                    helper="Admin, profile, billing-ready surface"
                    icon="mdi-view-grid-outline"
                    status="processing"
                />
            </div>

            <v-row class="mt-2" dense>
                <v-col
                    v-for="item in items"
                    :key="item.title"
                    cols="12"
                    md="6"
                >
                    <AppSectionCard :title="item.title" :subtitle="item.subtitle">
                        <p class="foundation-copy">{{ item.body }}</p>
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" lg="8">
                    <AppLineChart
                        title="Starter delivery velocity"
                        subtitle="Representative dashboard widget wrapper for analytics, billing, and admin modules."
                        :categories="velocityCategories"
                        :series="velocitySeries"
                    />
                </v-col>

                <v-col cols="12" lg="4">
                    <AppDonutChart
                        title="Component coverage"
                        subtitle="Where the current batch is focused."
                        :labels="coverageLabels"
                        :series="coverageSeries"
                        :colors="coverageColors"
                    />
                </v-col>

                <v-col cols="12">
                    <AppSectionCard
                        title="Upcoming groups"
                        subtitle="The next batches expand the UI system into dialogs, dashboard widgets, billing cards, and richer form inputs."
                    >
                        <div class="foundation-badges">
                            <AppStatusBadge status="draft" label="AppTextField" />
                            <AppStatusBadge status="draft" label="AppTextarea" />
                            <AppStatusBadge status="draft" label="AppFilterBar" />
                            <AppStatusBadge status="draft" label="AppTimeline" />
                            <AppStatusBadge status="draft" label="Payment widgets" />
                        </div>
                    </AppSectionCard>
                </v-col>

                <v-col cols="12">
                    <AppSectionCard title="Empty-state pattern" subtitle="Reusable default for table, page, and card empty states.">
                        <AppEmptyState
                            title="No widgets configured"
                            text="Use this pattern when a module has no seeded records, no filters matched, or a user has not created their first item yet."
                        >
                            <template #actions>
                                <v-btn color="primary" prepend-icon="mdi-plus">Create item</v-btn>
                                <v-btn variant="text">Read docs</v-btn>
                            </template>
                        </AppEmptyState>
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" md="6">
                    <AppSectionCard title="Inline alert" subtitle="Use for form, page, or panel feedback.">
                        <AppAlert
                            title="Payments still need real merchant credentials"
                            message="The billing baseline is scaffolded, but sandbox or production merchant details must be configured before live checkout testing."
                            type="warning"
                        />
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" md="6">
                    <AppSectionCard title="Section banner" subtitle="Use for module-wide system messaging.">
                        <AppBanner
                            title="Starter review note"
                            message="Commercial starter work should keep extracting stable primitives from real modules rather than building a detached component zoo."
                            type="success"
                        />
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" md="6">
                    <AppSectionCard title="File input" subtitle="Shared upload field wrapper for billing, imports, and content tools.">
                        <AppFileInput
                            v-model="demoFile"
                            label="Attach example file"
                            hint="Demonstrates the shared file-field API."
                            accept=".pdf,.png,.jpg"
                        />
                    </AppSectionCard>
                </v-col>

                <v-col cols="12" md="6">
                    <AppSectionCard title="Modal and drawer" subtitle="Interaction primitives for forms, detail views, and secondary flows.">
                        <div class="foundation-actions">
                            <v-btn color="primary" @click="demoModal = true">Open modal</v-btn>
                            <v-btn variant="outlined" @click="demoDrawer = true">Open drawer</v-btn>
                        </div>
                    </AppSectionCard>
                </v-col>

                <v-col cols="12">
                    <AppSectionCard title="Timeline pattern" subtitle="Starter-ready structure for activity feeds, payment events, and rollout history.">
                        <AppTimeline :items="timelineItems" />
                    </AppSectionCard>
                </v-col>
            </v-row>
        </div>

        <AppModal v-model="demoModal" title="Starter modal" subtitle="Use for focused create/edit flows.">
            <div class="foundation-modal">
                <AppTextField label="Example field" placeholder="Shared text input" />
                <AppTextarea label="Example notes" placeholder="Shared textarea for longer content" />
            </div>

            <template #actions>
                <v-btn variant="text" @click="demoModal = false">Close</v-btn>
                <v-btn color="primary" @click="demoModal = false">Confirm</v-btn>
            </template>
        </AppModal>

        <AppDrawer v-model="demoDrawer" title="Starter drawer" subtitle="Use for side-panel detail views and quick tools.">
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
        "title": "Foundation",
        "requiresAuth": true,
        "adminOnly": true
    }
}
</route>

<script setup>
import { ref } from 'vue';

const items = [
    {
        title: 'Page scaffolding',
        subtitle: 'Headers, cards, and empty states',
        body: 'AppPageHeader, AppSectionCard, and AppEmptyState give every module a consistent editorial structure instead of ad hoc page chrome.',
    },
    {
        title: 'Form primitives',
        subtitle: 'Starter-level select and autocomplete',
        body: 'AppSelect and AppAutocomplete sit on top of Vuetify but keep the starter API stable while we grow advanced field behavior and defaults.',
    },
    {
        title: 'Data display',
        subtitle: 'Status, pagination, and table support',
        body: 'AppStatusBadge and AppPaginationBar let admin, billing, and reporting screens share the same presentation logic for state and paginated lists.',
    },
    {
        title: 'Widgets',
        subtitle: 'Dashboard-ready charts and stat cards',
        body: 'AppStatCard, AppLineChart, and AppDonutChart are the first real dashboard widgets, ready to be reused for admin, revenue, and system-health surfaces.',
    },
];

const velocityCategories = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const velocitySeries = [
    { name: 'Components', data: [2, 4, 5, 8, 10, 12, 15] },
    { name: 'Modules adopting kit', data: [1, 1, 2, 2, 3, 3, 4] },
];

const coverageLabels = ['Forms', 'Data', 'Widgets', 'Dialogs'];
const coverageSeries = [34, 28, 22, 16];
const coverageColors = ['#006a4a', '#b45309', '#0369a1', '#9f1239'];
const demoModal = ref(false);
const demoDrawer = ref(false);
const demoFile = ref(null);
const timelineItems = [
    {
        id: 1,
        title: 'Commercial UI kit baseline',
        time: 'Current',
        text: 'Shared headers, badges, chart widgets, notification surfaces, and form wrappers are now part of the starter itself.',
        type: 'success',
    },
    {
        id: 2,
        title: 'Interaction layer expansion',
        time: 'Next',
        text: 'Dialogs, drawers, filter bars, file fields, and timelines give module teams a faster starting point without rewriting shell behavior.',
        type: 'info',
    },
    {
        id: 3,
        title: 'Module extraction pass',
        time: 'After',
        text: 'Billing, dashboard, and admin screens will keep donating stable patterns back into the shared kit.',
        type: 'warning',
    },
];
</script>

<style scoped>
.foundation-page {
    padding: 2rem 1.25rem 4rem;
}

.foundation-page__wrap {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    gap: 1.5rem;
}

.foundation-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
}

.foundation-copy {
    margin: 0;
    color: rgba(17, 34, 51, 0.75);
    line-height: 1.7;
}

.foundation-badges {
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.foundation-actions,
.foundation-modal {
    display: grid;
    gap: 0.75rem;
}

@media (max-width: 960px) {
    .foundation-page {
        padding: 1.75rem 1rem 3rem;
    }

    .foundation-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .foundation-page {
        padding-inline: 0.75rem;
    }

    .foundation-stats {
        grid-template-columns: 1fr;
    }
}
</style>
