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

        <AppSectionCard
            title="Your subscriptions"
            subtitle="Cancel a subscription at any time — it takes effect immediately through PayFast."
        >
            <div v-if="billing.subscriptionsLoading && !billing.subscriptions.length" class="billing-page__loading">
                <AppSkeleton v-for="n in 2" :key="n" height="64px" />
            </div>

            <div v-else-if="billing.subscriptions.length" class="subscription-list">
                <div v-for="sub in billing.subscriptions" :key="sub.id" class="subscription-row">
                    <div class="subscription-row__icon">
                        <v-icon icon="mdi-sync" size="20" />
                    </div>
                    <div class="subscription-row__meta">
                        <div class="subscription-row__title">
                            <span>{{ sub.item_name }}</span>
                            <AppStatusBadge :status="sub.status" :label="sub.status_label" />
                        </div>
                        <span class="subscription-row__detail">
                            {{ formatMoney(sub.recurring_amount) }} / cycle · {{ sub.merchant_payment_id }}
                        </span>
                        <span class="subscription-row__detail"
                            >Next billing: {{ sub.billing_date || 'Not scheduled' }}</span
                        >
                    </div>
                    <v-btn
                        v-if="!['cancelled', 'failed'].includes(sub.status)"
                        variant="text"
                        size="small"
                        color="error"
                        :loading="cancellingId === sub.id"
                        @click="cancelTarget = sub"
                    >
                        Cancel
                    </v-btn>
                </div>
            </div>

            <AppEmptyState
                v-else
                title="No subscriptions yet"
                text="Start a subscription below to see it appear here."
                icon="mdi-sync"
            />
        </AppSectionCard>

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

        <ConfirmDialog
            :model-value="Boolean(cancelTarget)"
            title="Cancel this subscription?"
            :text="`This cancels ${cancelTarget?.item_name ?? ''} through PayFast immediately. This cannot be undone.`"
            confirm-label="Cancel subscription"
            confirm-color="error"
            :loading="Boolean(cancellingId)"
            @update:model-value="cancelTarget = null"
            @cancel="cancelTarget = null"
            @confirm="confirmCancel"
        />

        <ConfirmDialog
            :model-value="showLeaveConfirm"
            title="Discard unsaved checkout details?"
            text="You've started filling in a checkout, but haven't submitted it yet. Leaving now will discard it."
            confirm-label="Discard"
            confirm-color="error"
            @update:model-value="cancelLeave"
            @cancel="cancelLeave"
            @confirm="confirmLeave"
        />
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
import { computed, onMounted, reactive, ref } from 'vue';

import AppEmptyState from '../../components/AppEmptyState.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppSelect from '../../components/AppSelect.vue';
import AppSkeleton from '../../components/AppSkeleton.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import AppTextField from '../../components/AppTextField.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormActions from '../../components/FormActions.vue';
import FormStatusAlert from '../../components/FormStatusAlert.vue';
import PaymentEventList from '../../components/PaymentEventList.vue';
import PaymentStatusCard from '../../components/PaymentStatusCard.vue';
import SubscriptionStatusCard from '../../components/SubscriptionStatusCard.vue';
import { useUnsavedChanges } from '../../composables/useUnsavedChanges';
import { useBillingStore } from '../../stores/billing';
import { useSessionStore } from '../../stores/session';

const billing = useBillingStore();
const session = useSessionStore();
const formError = ref('');
const cancelTarget = ref(null);
const cancellingId = ref(null);

const form = reactive({
    mode: 'payment',
    item_name: '',
    amount: '',
    billing_date: '',
});

// Set once checkout genuinely succeeds — startPayFastCheckout navigates the browser
// away to PayFast, which fires the same beforeunload event as an accidental tab
// close. Without this, a user who intentionally finishes checkout would still see
// the "leave site, unsaved changes?" browser prompt on their way out.
const leavingForCheckout = ref(false);
const isFormDirty = computed(
    () => !leavingForCheckout.value && Boolean(form.item_name || form.amount || form.billing_date)
);
const { showLeaveConfirm, confirmLeave, cancelLeave } = useUnsavedChanges(isFormDirty);

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

    if (result.ok) {
        leavingForCheckout.value = true;

        return;
    }

    formError.value = result.message;
};

const confirmCancel = async () => {
    if (!cancelTarget.value) {
        return;
    }

    cancellingId.value = cancelTarget.value.id;
    const result = await billing.cancelSubscription(cancelTarget.value.id);
    cancellingId.value = null;
    cancelTarget.value = null;

    if (!result.ok) {
        formError.value = result.message;
    }
};

onMounted(async () => {
    await Promise.all([billing.fetch(), billing.fetchSubscriptions()]);

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

.billing-page__loading {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
}

.subscription-list {
    display: flex;
    flex-direction: column;
}

.subscription-row {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 0.9rem 0.25rem;
    border-bottom: 1px solid var(--rw-border);
}

.subscription-row:last-child {
    border-bottom: none;
}

.subscription-row__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.75rem;
    background: var(--rw-surface-2);
    color: var(--rw-muted);
    flex-shrink: 0;
}

.subscription-row__meta {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    flex: 1;
    min-width: 0;
}

.subscription-row__title {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--rw-ink);
}

.subscription-row__detail {
    font-size: 0.8rem;
    color: var(--rw-muted);
}
</style>
