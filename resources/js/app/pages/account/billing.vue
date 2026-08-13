<template>
    <div class="billing-page">
        <AppPageHeader
            eyebrow="Account"
            title="Billing"
            subtitle="Your payment and subscription history, and a real PayFast checkout — this is live, not a demo."
        />

        <div class="billing-page__grid">
            <PaymentStatusCard
                v-if="billing.latestPayment"
                title="Latest payment"
                :amount="formatMoney(billing.latestPayment.amount_gross ?? billing.latestPayment.amount_requested)"
                :reference="billing.latestPayment.merchant_payment_id"
                :status="billing.latestPayment.status"
                :status-label="billing.latestPayment.status_label"
                :provider="billing.latestPayment.provider"
                :customer="billing.latestPayment.customer_name || '—'"
                :requested-at="formatDate(billing.latestPayment.initiated_at)"
                :settled-at="billing.latestPayment.paid_at ? formatDate(billing.latestPayment.paid_at) : 'Awaiting ITN'"
            />
            <AppSectionCard v-else title="Latest payment" subtitle="No payments yet">
                <AppEmptyState
                    title="No payments yet"
                    text="Start a one-time payment below to see it appear here once PayFast confirms it."
                    icon="mdi-credit-card-outline"
                />
            </AppSectionCard>

            <SubscriptionStatusCard
                v-if="billing.latestSubscription"
                title="Subscription"
                :amount="`${formatMoney(billing.latestSubscription.recurring_amount)} / cycle`"
                :plan="billing.latestSubscription.item_name"
                :status="billing.latestSubscription.status"
                :status-label="billing.latestSubscription.status_label"
                :billing-date="billing.latestSubscription.billing_date || 'Not scheduled'"
                :cycles="String(billing.latestSubscription.cycles ?? 0)"
                :terminal="billing.latestSubscription.is_terminal"
            />
            <AppSectionCard v-else title="Subscription" subtitle="No active subscription">
                <AppEmptyState
                    title="No subscription yet"
                    text="Start a subscription below to see it appear here once PayFast confirms it."
                    icon="mdi-sync"
                />
            </AppSectionCard>
        </div>

        <PaymentEventList title="Recent billing events" :events="timelineEvents" />

        <AppSectionCard
            title="Start a checkout"
            subtitle="Submits to the real PayFast sandbox — see config/payfast.php for credentials."
        >
            <FormStatusAlert :message="formError" type="error" />

            <v-form @submit.prevent="submit">
                <v-row dense>
                    <v-col cols="12" sm="4">
                        <AppSelect
                            v-model="form.mode"
                            :items="[
                                { title: 'One-time payment', value: 'payment' },
                                { title: 'Subscription', value: 'subscription' },
                            ]"
                            label="Type"
                        />
                    </v-col>
                    <v-col cols="12" sm="4">
                        <AppTextField v-model="form.item_name" label="Item name" required />
                    </v-col>
                    <v-col cols="12" sm="4">
                        <AppTextField
                            v-model="form.amount"
                            label="Amount (ZAR)"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                        />
                    </v-col>
                    <v-col v-if="form.mode === 'subscription'" cols="12" sm="6">
                        <AppTextField v-model="form.billing_date" label="First billing date" type="date" required />
                    </v-col>
                </v-row>

                <FormActions :loading="billing.checkingOut" submit-label="Continue to PayFast" />
            </v-form>
        </AppSectionCard>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "contextual",
        "title": "Billing",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { onMounted, reactive, ref } from 'vue';

import AppEmptyState from '../../components/AppEmptyState.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSelect from '../../components/AppSelect.vue';
import AppTextField from '../../components/AppTextField.vue';
import FormActions from '../../components/FormActions.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import PaymentEventList from '../../components/PaymentEventList.vue';
import PaymentStatusCard from '../../components/PaymentStatusCard.vue';
import SubscriptionStatusCard from '../../components/SubscriptionStatusCard.vue';
import { useBillingStore } from '../../stores/billing';
import { useSessionStore } from '../../stores/session';

const billing = useBillingStore();
const session = useSessionStore();
const formError = ref('');

const form = reactive({
    mode: 'payment',
    item_name: '',
    amount: '',
    billing_date: '',
});

const timelineEvents = ref([]);

const formatMoney = (value) => {
    const amount = Number(value ?? 0);

    return `R ${amount.toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString() : '—');

const submit = async () => {
    formError.value = '';

    if (!form.item_name || !form.amount || (form.mode === 'subscription' && !form.billing_date)) {
        formError.value = 'Fill in the required fields before continuing.';

        return;
    }

    const payload = {
        item_name: form.item_name,
        amount: form.amount,
        name_first: session.user?.name?.split(' ')?.[0],
        email_address: session.user?.email,
        ...(form.mode === 'subscription' ? { recurring_amount: form.amount, billing_date: form.billing_date } : {}),
    };

    const result = await billing.checkout(form.mode, payload);

    if (!result.ok) {
        formError.value = result.message;
    }
};

onMounted(async () => {
    await billing.fetch();

    timelineEvents.value = billing.recentEvents.map((event) => ({
        id: event.id,
        title: event.event_type,
        time: formatDate(event.received_at),
        text: event.payment_id ? `Payment #${event.payment_id}` : `Subscription #${event.subscription_id}`,
    }));
});
</script>

<style scoped>
.billing-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.billing-page__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
}
</style>
