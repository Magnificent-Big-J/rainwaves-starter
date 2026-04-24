<template>
    <AppSectionCard :title="title" :subtitle="subtitle">
        <div class="payment-card">
            <div class="payment-card__top">
                <div>
                    <div class="payment-card__amount">{{ amount }}</div>
                    <div class="payment-card__meta">{{ reference }}</div>
                </div>
                <AppStatusBadge :status="status" :label="statusLabel" />
            </div>

            <div class="payment-card__grid">
                <div class="payment-card__item">
                    <span>Provider</span>
                    <strong>{{ provider }}</strong>
                </div>
                <div class="payment-card__item">
                    <span>Customer</span>
                    <strong>{{ customer }}</strong>
                </div>
                <div class="payment-card__item">
                    <span>Requested</span>
                    <strong>{{ requestedAt }}</strong>
                </div>
                <div class="payment-card__item">
                    <span>Settlement</span>
                    <strong>{{ settledAt }}</strong>
                </div>
            </div>
        </div>
    </AppSectionCard>
</template>

<script setup>
defineProps({
    title: { type: String, default: 'Payment' },
    subtitle: { type: String, default: 'Starter billing card primitive' },
    amount: { type: String, required: true },
    reference: { type: String, required: true },
    status: { type: String, required: true },
    statusLabel: { type: String, default: null },
    provider: { type: String, default: 'PayFast' },
    customer: { type: String, default: 'Unknown customer' },
    requestedAt: { type: String, default: 'Not scheduled' },
    settledAt: { type: String, default: 'Pending' },
});
</script>

<style scoped>
.payment-card {
    display: grid;
    gap: 1rem;
}

.payment-card__top {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
}

.payment-card__amount {
    font-size: 1.7rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}

.payment-card__meta {
    margin-top: 0.25rem;
    font-size: 0.82rem;
    color: var(--rw-muted);
}

.payment-card__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.payment-card__item {
    display: grid;
    gap: 0.2rem;
}

.payment-card__item span {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--rw-muted);
}

.payment-card__item strong {
    font-size: 0.9rem;
}
</style>
