<template>
    <AppSectionCard :title="title" :subtitle="subtitle">
        <div class="subscription-card">
            <div class="subscription-card__top">
                <div>
                    <div class="subscription-card__amount">{{ amount }}</div>
                    <div class="subscription-card__meta">{{ plan }}</div>
                </div>
                <AppStatusBadge :status="status" :label="statusLabel" />
            </div>

            <div class="subscription-card__stats">
                <AppStatCard
                    label="Billing date"
                    :value="billingDate"
                    icon="mdi-calendar-month-outline"
                    helper="Next recurring charge"
                />
                <AppStatCard
                    label="Cycles"
                    :value="cycles"
                    icon="mdi-sync"
                    :status="terminal ? 'completed' : 'processing'"
                    helper="0 means ongoing"
                />
            </div>
        </div>
    </AppSectionCard>
</template>

<script setup>
defineProps({
    title: { type: String, default: 'Subscription' },
    subtitle: { type: String, default: 'Starter recurring billing card primitive' },
    amount: { type: String, required: true },
    plan: { type: String, required: true },
    status: { type: String, required: true },
    statusLabel: { type: String, default: null },
    billingDate: { type: String, default: 'Not scheduled' },
    cycles: { type: [String, Number], default: '0' },
    terminal: { type: Boolean, default: false },
});
</script>

<style scoped>
.subscription-card {
    display: grid;
    gap: 1rem;
}

.subscription-card__top {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
}

.subscription-card__amount {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.subscription-card__meta {
    margin-top: 0.25rem;
    font-size: 0.82rem;
    color: var(--starter-muted);
}

.subscription-card__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}
</style>
