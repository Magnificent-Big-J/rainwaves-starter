<template>
    <div class="payfast-test-page">
        <AppPageHeader
            eyebrow="Payments"
            title="PayFast browser test"
            subtitle="Generate starter checkout forms against the installed PayFast package."
        >
            <template #metrics>
                <AppStatusBadge status="active" label="Local v2" />
                <AppStatusBadge :status="generatedForm.action ? 'active' : 'pending'" :label="generatedForm.action ? 'Form ready' : 'No form'" />
            </template>
        </AppPageHeader>

        <v-tabs v-model="view" class="surface-tabs" density="comfortable">
            <v-tab value="checkout" prepend-icon="mdi-credit-card-check-outline">Checkout</v-tab>
            <v-tab value="records" prepend-icon="mdi-database-search-outline">Records</v-tab>
            <v-tab value="subscriptions" prepend-icon="mdi-repeat-variant">Subscriptions</v-tab>
            <v-tab value="events" prepend-icon="mdi-timeline-text-outline">Events</v-tab>
        </v-tabs>

        <FormStatusAlert class="page-alert" :message="message" :type="messageType" />

        <v-window v-model="view" class="surface-window">
            <v-window-item value="checkout">
                <div class="payfast-test-grid">
                    <AppSectionCard title="Checkout payload">
                        <v-tabs v-model="mode" class="mode-tabs" density="comfortable">
                            <v-tab value="one-time" prepend-icon="mdi-credit-card-outline">One-time</v-tab>
                            <v-tab value="subscription" prepend-icon="mdi-repeat-variant">Subscription</v-tab>
                        </v-tabs>

                        <v-window v-model="mode" class="form-window">
                            <v-window-item value="one-time">
                                <v-form class="form-grid" @submit.prevent="generateForm">
                                    <AppTextField v-model="oneTime.amount" label="Amount" type="number" step="0.01" min="0.01" />
                                    <AppTextField v-model="oneTime.item_name" label="Item name" />
                                    <AppTextField v-model="oneTime.item_description" label="Item description" />
                                    <AppTextField v-model="oneTime.email_address" label="Email" type="email" />
                                    <AppTextField v-model="oneTime.name_first" label="First name" />
                                    <AppTextField v-model="oneTime.name_last" label="Last name" />
                                    <AppTextField v-model="oneTime.m_payment_id" label="Merchant payment ID" />

                                    <div class="form-actions">
                                        <v-btn color="primary" prepend-icon="mdi-file-code-outline" type="submit" :loading="loading">
                                            Generate form
                                        </v-btn>
                                        <v-btn variant="outlined" prepend-icon="mdi-refresh" type="button" @click="resetOneTime">
                                            Reset
                                        </v-btn>
                                    </div>
                                </v-form>
                            </v-window-item>

                            <v-window-item value="subscription">
                                <v-form class="form-grid" @submit.prevent="generateForm">
                                    <AppTextField v-model="subscription.amount" label="Initial amount" type="number" step="0.01" min="0.01" />
                                    <AppTextField v-model="subscription.recurring_amount" label="Recurring amount" type="number" step="0.01" min="0.01" />
                                    <AppTextField v-model="subscription.item_name" label="Item name" />
                                    <AppTextField v-model="subscription.billing_date" label="Billing date" type="date" />
                                    <AppSelect
                                        v-model="subscription.frequency"
                                        :items="frequencyOptions"
                                        label="Frequency"
                                        :clearable="false"
                                    />
                                    <AppTextField v-model="subscription.cycles" label="Cycles" type="number" min="0" step="1" />
                                    <AppTextField v-model="subscription.email_address" label="Email" type="email" />
                                    <AppTextField v-model="subscription.m_payment_id" label="Merchant payment ID" />

                                    <div class="form-actions">
                                        <v-btn color="primary" prepend-icon="mdi-file-code-outline" type="submit" :loading="loading">
                                            Generate form
                                        </v-btn>
                                        <v-btn variant="outlined" prepend-icon="mdi-refresh" type="button" @click="resetSubscription">
                                            Reset
                                        </v-btn>
                                    </div>
                                </v-form>
                            </v-window-item>
                        </v-window>
                    </AppSectionCard>

                    <div class="result-column">
                        <AppSectionCard title="Generated form" :subtitle="generatedForm.action || 'Awaiting payload'">
                            <div v-if="generatedForm.html" class="generated-form">
                                <div class="generated-form__preview" v-html="generatedForm.html" />
                                <div class="generated-form__actions">
                                    <v-btn color="primary" prepend-icon="mdi-open-in-new" @click="submitGeneratedForm">
                                        Submit to PayFast
                                    </v-btn>
                                    <v-btn variant="outlined" prepend-icon="mdi-content-copy" @click="copyHtml">
                                        Copy HTML
                                    </v-btn>
                                </div>
                            </div>

                            <AppEmptyState
                                v-else
                                title="No generated form"
                                text="Choose a payload and generate a checkout form."
                            />
                        </AppSectionCard>

                        <AppSectionCard title="Signed fields">
                            <div v-if="generatedForm.fields.length" class="field-list">
                                <div v-for="field in generatedForm.fields" :key="field.name" class="field-row">
                                    <span class="field-row__name">{{ field.name }}</span>
                                    <span class="field-row__value">{{ field.value }}</span>
                                </div>
                            </div>
                            <AppEmptyState v-else title="No fields" text="Generated PayFast fields will appear here." />
                        </AppSectionCard>
                    </div>
                </div>
            </v-window-item>

            <v-window-item value="records">
                <div class="records-grid">
                    <AppSectionCard title="Payments" :subtitle="`${records.payments.length} recent records`">
                        <template #actions>
                            <v-btn size="small" variant="outlined" prepend-icon="mdi-refresh" :loading="recordsLoading" @click="refreshRecords">
                                Refresh
                            </v-btn>
                        </template>

                        <div v-if="records.payments.length" class="record-table">
                            <div class="record-table__head record-table__row--payments">
                                <span>Reference</span>
                                <span>Item</span>
                                <span>Amount</span>
                                <span>Status</span>
                                <span>PayFast ID</span>
                                <span>ITN</span>
                            </div>
                            <div v-for="payment in records.payments" :key="payment.id" class="record-table__row record-table__row--payments">
                                <span class="mono">{{ payment.merchant_payment_id }}</span>
                                <span>{{ payment.item_name }}</span>
                                <span>{{ money(payment.amount_requested) }}</span>
                                <AppStatusBadge :status="payment.status" />
                                <span class="mono muted">{{ payment.payfast_payment_id || 'pending' }}</span>
                                <div class="row-actions">
                                    <v-btn size="x-small" color="success" variant="tonal" @click="simulateItn('payment', payment.id, 'COMPLETE')">Complete</v-btn>
                                    <v-btn size="x-small" color="warning" variant="tonal" @click="simulateItn('payment', payment.id, 'PENDING')">Pending</v-btn>
                                    <v-btn size="x-small" color="error" variant="tonal" @click="simulateItn('payment', payment.id, 'FAILED')">Fail</v-btn>
                                </div>
                            </div>
                        </div>
                        <AppEmptyState v-else title="No payments" text="Generated one-time checkout records will appear here." />
                    </AppSectionCard>

                    <AppSectionCard title="Subscriptions" :subtitle="`${records.subscriptions.length} recent records`">
                        <div v-if="records.subscriptions.length" class="record-table">
                            <div class="record-table__head record-table__row--subscriptions">
                                <span>Reference</span>
                                <span>Item</span>
                                <span>Recurring</span>
                                <span>Status</span>
                                <span>Token</span>
                                <span>ITN</span>
                            </div>
                            <div v-for="subscriptionRecord in records.subscriptions" :key="subscriptionRecord.id" class="record-table__row record-table__row--subscriptions">
                                <span class="mono">{{ subscriptionRecord.merchant_payment_id }}</span>
                                <span>{{ subscriptionRecord.item_name }}</span>
                                <span>{{ money(subscriptionRecord.recurring_amount) }}</span>
                                <AppStatusBadge :status="subscriptionRecord.status" />
                                <span class="mono muted">{{ subscriptionRecord.token || 'pending' }}</span>
                                <div class="row-actions">
                                    <v-btn size="x-small" color="success" variant="tonal" @click="simulateItn('subscription', subscriptionRecord.id, 'COMPLETE')">Complete</v-btn>
                                    <v-btn size="x-small" color="warning" variant="tonal" @click="simulateItn('subscription', subscriptionRecord.id, 'PENDING')">Pending</v-btn>
                                    <v-btn size="x-small" color="error" variant="tonal" @click="simulateItn('subscription', subscriptionRecord.id, 'FAILED')">Fail</v-btn>
                                </div>
                            </div>
                        </div>
                        <AppEmptyState v-else title="No subscriptions" text="Generated subscription checkout records will appear here." />
                    </AppSectionCard>
                </div>
            </v-window-item>

            <v-window-item value="subscriptions">
                <div class="subscriptions-grid">
                    <AppSectionCard title="Subscriptions" :subtitle="`${records.subscriptions.length} recent records`">
                        <template #actions>
                            <v-btn size="small" variant="outlined" prepend-icon="mdi-refresh" :loading="recordsLoading" @click="refreshRecords">
                                Refresh
                            </v-btn>
                        </template>

                        <div v-if="records.subscriptions.length" class="subscription-list">
                            <button
                                v-for="subscriptionRecord in records.subscriptions"
                                :key="subscriptionRecord.id"
                                type="button"
                                class="subscription-list-item"
                                :class="{ 'subscription-list-item--active': selectedSubscriptionId === subscriptionRecord.id }"
                                @click="selectSubscription(subscriptionRecord.id)"
                            >
                                <span>
                                    <span class="subscription-list-item__title">{{ subscriptionRecord.item_name }}</span>
                                    <span class="mono muted">{{ subscriptionRecord.merchant_payment_id }}</span>
                                </span>
                                <span class="subscription-list-item__meta">
                                    <AppStatusBadge :status="subscriptionRecord.status" />
                                    <span>{{ money(subscriptionRecord.recurring_amount) }}</span>
                                </span>
                                <span class="subscription-list-item__meta">
                                    <span>{{ frequencyLabel(subscriptionRecord.frequency) }}</span>
                                    <span>{{ subscriptionRecord.billing_date || 'pending' }}</span>
                                </span>
                            </button>
                        </div>
                        <AppEmptyState v-else title="No subscriptions" text="Generated subscription checkout records will appear here." />
                    </AppSectionCard>

                    <div class="subscription-detail-column">
                        <AppSectionCard v-if="selectedSubscription" title="Subscription detail" :subtitle="selectedSubscription.merchant_payment_id">
                            <div class="detail-grid">
                                <div class="detail-cell">
                                    <span>Status</span>
                                    <AppStatusBadge :status="selectedSubscription.status" />
                                </div>
                                <div class="detail-cell">
                                    <span>Payment status</span>
                                    <strong>{{ selectedSubscription.payment_status || 'pending' }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Token</span>
                                    <strong class="mono">{{ selectedSubscription.token || 'pending' }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Recurring</span>
                                    <strong>{{ money(selectedSubscription.recurring_amount) }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Billing date</span>
                                    <strong>{{ selectedSubscription.billing_date || 'pending' }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Frequency</span>
                                    <strong>{{ frequencyLabel(selectedSubscription.frequency) }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Cycles</span>
                                    <strong>{{ selectedSubscription.cycles }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Customer</span>
                                    <strong>{{ selectedSubscription.customer_email || 'pending' }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Initiated</span>
                                    <strong>{{ dateTime(selectedSubscription.initiated_at || selectedSubscription.created_at) }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Activated</span>
                                    <strong>{{ dateTime(selectedSubscription.activated_at) }}</strong>
                                </div>
                                <div class="detail-cell">
                                    <span>Cancelled</span>
                                    <strong>{{ dateTime(selectedSubscription.cancelled_at) }}</strong>
                                </div>
                            </div>
                        </AppSectionCard>

                        <AppSectionCard v-if="selectedSubscription" title="Local lifecycle actions" subtitle="Signed test ITNs">
                            <div class="row-actions row-actions--wide">
                                <v-btn size="small" color="success" variant="tonal" prepend-icon="mdi-check-circle-outline" @click="simulateItn('subscription', selectedSubscription.id, 'COMPLETE')">
                                    Complete
                                </v-btn>
                                <v-btn size="small" color="warning" variant="tonal" prepend-icon="mdi-timer-sand" @click="simulateItn('subscription', selectedSubscription.id, 'PENDING')">
                                    Pending
                                </v-btn>
                                <v-btn size="small" color="error" variant="tonal" prepend-icon="mdi-alert-circle-outline" @click="simulateItn('subscription', selectedSubscription.id, 'FAILED')">
                                    Fail
                                </v-btn>
                                <v-btn size="small" color="error" variant="outlined" prepend-icon="mdi-cancel" @click="simulateItn('subscription', selectedSubscription.id, 'CANCELLED')">
                                    Cancel
                                </v-btn>
                            </div>
                        </AppSectionCard>

                        <AppSectionCard v-if="selectedSubscription" title="Native PayFast actions" :subtitle="selectedSubscription.token || 'Token pending'">
                            <div class="native-actions">
                                <div class="row-actions row-actions--wide">
                                    <v-btn size="small" variant="tonal" prepend-icon="mdi-cloud-search-outline" :disabled="!selectedSubscription.token" :loading="isSubscriptionActionLoading('fetch')" @click="runSubscriptionAction('fetch')">
                                        Fetch
                                    </v-btn>
                                    <v-btn size="small" variant="tonal" prepend-icon="mdi-pause-circle-outline" :disabled="!selectedSubscription.token" :loading="isSubscriptionActionLoading('pause')" @click="runSubscriptionAction('pause')">
                                        Pause
                                    </v-btn>
                                    <v-btn size="small" variant="tonal" prepend-icon="mdi-play-circle-outline" :disabled="!selectedSubscription.token" :loading="isSubscriptionActionLoading('unpause')" @click="runSubscriptionAction('unpause')">
                                        Unpause
                                    </v-btn>
                                    <v-btn size="small" color="error" variant="outlined" prepend-icon="mdi-cancel" :disabled="!selectedSubscription.token" :loading="isSubscriptionActionLoading('cancel')" @click="runSubscriptionAction('cancel')">
                                        Cancel
                                    </v-btn>
                                    <v-btn size="small" variant="outlined" prepend-icon="mdi-credit-card-refresh-outline" :disabled="!selectedSubscription.token" :loading="isSubscriptionActionLoading('card_update_link')" @click="runSubscriptionAction('card_update_link')">
                                        Card link
                                    </v-btn>
                                </div>

                                <div class="action-form-grid">
                                    <AppTextField v-model="subscriptionActionForm.pause_cycles" label="Pause cycles" type="number" min="1" max="24" step="1" />
                                    <AppTextField v-model="subscriptionActionForm.update_amount" label="Update amount" type="number" step="0.01" min="0.01" />
                                    <AppSelect
                                        v-model="subscriptionActionForm.update_frequency"
                                        :items="frequencyOptions"
                                        label="Update frequency"
                                    />
                                    <AppTextField v-model="subscriptionActionForm.update_cycles" label="Update cycles" type="number" min="0" step="1" />
                                    <AppTextField v-model="subscriptionActionForm.update_run_date" label="Update run date" type="date" />
                                    <AppTextField v-model="subscriptionActionForm.adhoc_amount" label="Ad hoc amount" type="number" step="0.01" min="0.01" />
                                    <AppTextField v-model="subscriptionActionForm.adhoc_item_name" label="Ad hoc item" />
                                    <AppTextField v-model="subscriptionActionForm.adhoc_item_description" label="Ad hoc description" />
                                </div>

                                <div class="row-actions row-actions--wide">
                                    <v-btn size="small" variant="tonal" prepend-icon="mdi-pencil-outline" :disabled="!selectedSubscription.token" :loading="isSubscriptionActionLoading('update')" @click="runSubscriptionAction('update')">
                                        Update
                                    </v-btn>
                                    <v-btn size="small" variant="tonal" prepend-icon="mdi-cash-plus" :disabled="!selectedSubscription.token" :loading="isSubscriptionActionLoading('adhoc')" @click="runSubscriptionAction('adhoc')">
                                        Ad hoc charge
                                    </v-btn>
                                </div>

                                <div v-if="subscriptionActionResult" class="action-result">
                                    <a v-if="subscriptionActionResult.url" :href="subscriptionActionResult.url" target="_blank" rel="noopener">Open card update link</a>
                                    <pre>{{ JSON.stringify(subscriptionActionResult, null, 2) }}</pre>
                                </div>
                            </div>
                        </AppSectionCard>

                        <AppSectionCard v-if="selectedSubscription" title="Subscription events" :subtitle="`${selectedSubscriptionEvents.length} events`">
                            <div v-if="selectedSubscriptionEvents.length" class="event-list">
                                <details v-for="event in selectedSubscriptionEvents" :key="event.id" class="event-item">
                                    <summary>
                                        <span class="event-item__type">{{ event.event_type }}</span>
                                        <span class="mono">{{ event.event_ref || event.merchant_payment_id || 'no-ref' }}</span>
                                        <span class="muted">{{ dateTime(event.received_at || event.created_at) }}</span>
                                    </summary>
                                    <pre>{{ JSON.stringify(event.payload, null, 2) }}</pre>
                                </details>
                            </div>
                            <AppEmptyState v-else title="No subscription events" text="Subscription checkout and ITN events will appear here." />
                        </AppSectionCard>
                    </div>
                </div>
            </v-window-item>

            <v-window-item value="events">
                <AppSectionCard title="Payment events" :subtitle="`${records.events.length} recent events`">
                    <template #actions>
                        <v-btn size="small" variant="outlined" prepend-icon="mdi-refresh" :loading="recordsLoading" @click="refreshRecords">
                            Refresh
                        </v-btn>
                    </template>

                    <div v-if="records.events.length" class="event-list">
                        <details v-for="event in records.events" :key="event.id" class="event-item">
                            <summary>
                                <span class="event-item__type">{{ event.event_type }}</span>
                                <span class="mono">{{ event.event_ref || event.merchant_payment_id || 'no-ref' }}</span>
                                <span class="muted">{{ dateTime(event.received_at || event.created_at) }}</span>
                            </summary>
                            <pre>{{ JSON.stringify(event.payload, null, 2) }}</pre>
                        </details>
                    </div>
                    <AppEmptyState v-else title="No events" text="Checkout and ITN events will appear here." />
                </AppSectionCard>
            </v-window-item>
        </v-window>
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "default",
        "title": "PayFast Browser Test",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

import AppEmptyState from '../components/AppEmptyState.vue';
import AppPageHeader from '../components/AppPageHeader.vue';
import AppSectionCard from '../components/AppSectionCard.vue';
import AppSelect from '../components/AppSelect.vue';
import AppStatusBadge from '../components/AppStatusBadge.vue';
import AppTextField from '../components/AppTextField.vue';
import FormStatusAlert from '../components/FormStatusAlert.vue';

const mode = ref('one-time');
const view = ref('checkout');
const loading = ref(false);
const recordsLoading = ref(false);
const selectedSubscriptionId = ref(null);
const subscriptionActionLoading = ref('');
const subscriptionActionResult = ref(null);
const message = ref('');
const messageType = ref('success');

const oneTimeDefaults = () => ({
    amount: '150.00',
    item_name: 'Starter browser test',
    item_description: 'One-time checkout smoke',
    email_address: 'customer@rainwaves.test',
    name_first: 'Rainwaves',
    name_last: 'Customer',
    m_payment_id: `RW-BROWSER-${Date.now()}`,
});

const subscriptionDefaults = () => ({
    amount: '99.00',
    recurring_amount: '99.00',
    item_name: 'Starter subscription browser test',
    billing_date: nextBillingDate(),
    frequency: 3,
    cycles: 0,
    email_address: 'customer@rainwaves.test',
    m_payment_id: `SUB-BROWSER-${Date.now()}`,
});

const oneTime = reactive(oneTimeDefaults());
const subscription = reactive(subscriptionDefaults());

const generatedForm = reactive({
    html: '',
    action: '',
    fields: [],
});

const records = reactive({
    payments: [],
    subscriptions: [],
    events: [],
});

const subscriptionActionForm = reactive({
    pause_cycles: 1,
    update_amount: '',
    update_frequency: null,
    update_cycles: '',
    update_run_date: '',
    adhoc_amount: '',
    adhoc_item_name: '',
    adhoc_item_description: '',
});

const frequencyOptions = [
    { title: 'Daily', value: 1 },
    { title: 'Weekly', value: 2 },
    { title: 'Monthly', value: 3 },
    { title: 'Quarterly', value: 4 },
    { title: 'Bi-annual', value: 5 },
    { title: 'Annual', value: 6 },
];

const endpoint = computed(() => (
    mode.value === 'subscription'
        ? '/payments/payfast/subscriptions/initiate'
        : '/payments/payfast/initiate'
));

const payload = computed(() => {
    const source = mode.value === 'subscription' ? subscription : oneTime;

    return Object.fromEntries(
        Object.entries(source).filter(([, value]) => value !== null && value !== '')
    );
});

const selectedSubscription = computed(() => (
    records.subscriptions.find((subscriptionRecord) => subscriptionRecord.id === selectedSubscriptionId.value) || null
));

const selectedSubscriptionEvents = computed(() => {
    if (!selectedSubscription.value) {
        return [];
    }

    return records.events.filter((event) => event.subscription_id === selectedSubscription.value.id);
});

const generateForm = async () => {
    loading.value = true;
    message.value = '';

    try {
        const response = await fetch(endpoint.value, {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'text/html',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload.value),
        });

        const body = await response.text();

        if (!response.ok) {
            throw new Error(extractErrorMessage(body, response.status));
        }

        setGeneratedForm(body);
        messageType.value = 'success';
        message.value = 'PayFast form generated.';
        await refreshRecords();
    } catch (error) {
        generatedForm.html = '';
        generatedForm.action = '';
        generatedForm.fields = [];
        messageType.value = 'error';
        message.value = error?.message || 'Unable to generate PayFast form.';
    } finally {
        loading.value = false;
    }
};

const refreshRecords = async () => {
    recordsLoading.value = true;

    try {
        const response = await fetch('/payments/payfast/records', {
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Unable to load PayFast records (${response.status}).`);
        }

        const payload = await response.json();
        records.payments = payload.payments || [];
        records.subscriptions = payload.subscriptions || [];
        records.events = payload.events || [];
        ensureSelectedSubscription();
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.message || 'Unable to load PayFast records.';
    } finally {
        recordsLoading.value = false;
    }
};

const runSubscriptionAction = async (action) => {
    if (!selectedSubscription.value) {
        return;
    }

    subscriptionActionLoading.value = action;
    subscriptionActionResult.value = null;
    message.value = '';

    try {
        const response = await fetch('/payments/payfast/subscriptions/action', {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(subscriptionActionPayload(action)),
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.message || `Subscription ${action} failed (${response.status}).`);
        }

        subscriptionActionResult.value = payload.result;
        messageType.value = payload.result?.successful ? 'success' : 'warning';
        message.value = payload.result?.successful
            ? `Subscription ${actionLabel(action)} completed.`
            : `Subscription ${actionLabel(action)} returned ${payload.result?.provider_message || 'a non-success response'}.`;
        await refreshRecords();
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.message || `Unable to run subscription ${action}.`;
    } finally {
        subscriptionActionLoading.value = '';
    }
};

const subscriptionActionPayload = (action) => {
    const body = {
        id: selectedSubscription.value.id,
        action,
    };

    if (action === 'pause') {
        body.pause_cycles = subscriptionActionForm.pause_cycles || 1;
    }

    if (action === 'update') {
        body.update_amount = subscriptionActionForm.update_amount || undefined;
        body.update_frequency = subscriptionActionForm.update_frequency || undefined;
        body.update_cycles = subscriptionActionForm.update_cycles === '' ? undefined : subscriptionActionForm.update_cycles;
        body.update_run_date = subscriptionActionForm.update_run_date || undefined;
    }

    if (action === 'adhoc') {
        body.adhoc_amount = subscriptionActionForm.adhoc_amount || selectedSubscription.value.recurring_amount;
        body.adhoc_item_name = subscriptionActionForm.adhoc_item_name || `${selectedSubscription.value.item_name} ad hoc`;
        body.adhoc_item_description = subscriptionActionForm.adhoc_item_description || undefined;
    }

    return body;
};

const selectSubscription = (id) => {
    selectedSubscriptionId.value = id;
    subscriptionActionResult.value = null;
};

const ensureSelectedSubscription = () => {
    if (!records.subscriptions.length) {
        selectedSubscriptionId.value = null;

        return;
    }

    if (!records.subscriptions.some((subscriptionRecord) => subscriptionRecord.id === selectedSubscriptionId.value)) {
        selectedSubscriptionId.value = records.subscriptions[0].id;
    }
};

const isSubscriptionActionLoading = (action) => subscriptionActionLoading.value === action;

const simulateItn = async (type, id, paymentStatus) => {
    message.value = '';

    try {
        const response = await fetch('/payments/payfast/simulate-itn', {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                type,
                id,
                payment_status: paymentStatus,
            }),
        });

        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload?.message || `Unable to simulate ITN (${response.status}).`);
        }

        messageType.value = payload.result?.accepted ? 'success' : 'warning';
        message.value = payload.result?.accepted
            ? `${paymentStatus} ITN accepted for ${type}.`
            : `ITN rejected: ${payload.result?.reason || 'unknown reason'}.`;
        view.value = 'events';
        await refreshRecords();
    } catch (error) {
        messageType.value = 'error';
        message.value = error?.message || 'Unable to simulate ITN.';
    }
};

const setGeneratedForm = (html) => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const form = doc.querySelector('form');

    generatedForm.html = html;
    generatedForm.action = form?.getAttribute('action') || '';
    generatedForm.fields = [...doc.querySelectorAll('input[type="hidden"]')].map((input) => ({
        name: input.getAttribute('name') || '',
        value: input.getAttribute('value') || '',
    }));
};

const submitGeneratedForm = () => {
    if (!generatedForm.action || !generatedForm.fields.length) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = generatedForm.action;
    form.target = '_blank';
    form.rel = 'noopener';

    generatedForm.fields.forEach((field) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = field.name;
        input.value = field.value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    form.remove();
};

const copyHtml = async () => {
    if (!generatedForm.html) {
        return;
    }

    await navigator.clipboard.writeText(generatedForm.html);
    messageType.value = 'success';
    message.value = 'Generated HTML copied.';
};

const resetOneTime = () => {
    Object.assign(oneTime, oneTimeDefaults());
};

const resetSubscription = () => {
    Object.assign(subscription, subscriptionDefaults());
};

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function extractErrorMessage(body, status) {
    try {
        const parsed = JSON.parse(body);
        return parsed?.message || `PayFast form request failed with ${status}.`;
    } catch {
        return `PayFast form request failed with ${status}.`;
    }
}

function nextBillingDate() {
    const date = new Date();
    date.setDate(date.getDate() + 7);

    return date.toISOString().slice(0, 10);
}

function money(value) {
    if (value === null || value === undefined || value === '') {
        return 'R 0.00';
    }

    return `R ${Number(value).toFixed(2)}`;
}

function dateTime(value) {
    if (!value) {
        return 'pending';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

function frequencyLabel(value) {
    return frequencyOptions.find((option) => option.value === Number(value))?.title || 'Unknown';
}

function actionLabel(action) {
    return action.replaceAll('_', ' ');
}

function hydrateBrowserResult() {
    const params = new URLSearchParams(window.location.search);
    const result = params.get('payfast_result');

    if (!result) {
        return;
    }

    view.value = 'records';
    messageType.value = result === 'cancel' ? 'warning' : 'success';
    message.value = result === 'cancel'
        ? 'PayFast returned through the cancel URL.'
        : 'PayFast returned through the return URL.';
}

onMounted(() => {
    hydrateBrowserResult();
    refreshRecords();
});
</script>

<style scoped>
.payfast-test-page {
    display: grid;
    gap: 1.25rem;
}

.payfast-test-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(340px, 0.95fr);
    gap: 1.25rem;
    align-items: start;
}

.mode-tabs {
    margin: -0.3rem 0 1rem;
}

.surface-tabs {
    align-self: start;
    border-bottom: 1px solid var(--rw-border);
}

.surface-window,
.surface-window :deep(.v-window__container),
.surface-window :deep(.v-window-item) {
    overflow: visible;
}

.page-alert {
    max-width: 780px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    padding-top: 0.35rem;
}

.form-window,
.form-window :deep(.v-window__container),
.form-window :deep(.v-window-item) {
    overflow: visible;
}

.form-actions {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.result-column {
    display: grid;
    gap: 1.25rem;
}

.records-grid {
    display: grid;
    gap: 1.25rem;
}

.subscriptions-grid {
    display: grid;
    grid-template-columns: minmax(320px, 0.6fr) minmax(0, 1.4fr);
    gap: 1.25rem;
    align-items: start;
}

.subscription-detail-column {
    display: grid;
    gap: 1.25rem;
}

.subscription-list {
    display: grid;
    gap: 0.55rem;
}

.subscription-list-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.55rem;
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--rw-border);
    border-radius: 0.55rem;
    background: var(--rw-surface-soft);
    color: inherit;
    text-align: left;
    cursor: pointer;
}

.subscription-list-item--active {
    border-color: color-mix(in srgb, var(--rw-primary) 55%, var(--rw-border));
    background: color-mix(in srgb, var(--rw-surface) 86%, #00c88c);
}

.subscription-list-item__title {
    display: block;
    margin-bottom: 0.2rem;
    font-weight: 700;
}

.subscription-list-item__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    color: var(--rw-muted);
    font-size: 0.85rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.65rem;
}

.detail-cell {
    display: grid;
    gap: 0.28rem;
    min-width: 0;
    padding: 0.7rem;
    border: 1px solid var(--rw-border);
    border-radius: 0.55rem;
    background: var(--rw-surface-soft);
}

.detail-cell span {
    color: var(--rw-muted);
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
}

.detail-cell strong {
    min-width: 0;
    overflow-wrap: anywhere;
    font-size: 0.92rem;
}

.native-actions {
    display: grid;
    gap: 1rem;
}

.action-form-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    padding-top: 0.2rem;
}

.action-result {
    display: grid;
    gap: 0.6rem;
}

.action-result a {
    color: var(--rw-primary);
    font-weight: 700;
}

.action-result pre {
    max-height: 260px;
    margin: 0;
    padding: 0.85rem;
    overflow: auto;
    border: 1px solid var(--rw-border);
    border-radius: 0.55rem;
    background: var(--rw-surface-soft);
    color: var(--rw-muted);
    font-size: 0.76rem;
}

.record-table {
    display: grid;
    gap: 0.45rem;
    overflow-x: auto;
}

.record-table__head,
.record-table__row {
    display: grid;
    gap: 0.75rem;
    align-items: center;
    min-width: 840px;
}

.record-table__head {
    padding: 0 0.65rem 0.35rem;
    color: var(--rw-muted);
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
}

.record-table__row {
    padding: 0.65rem;
    border: 1px solid var(--rw-border);
    border-radius: 0.55rem;
    background: var(--rw-surface-soft);
}

.record-table__row--payments {
    grid-template-columns: minmax(150px, 0.9fr) minmax(160px, 1fr) minmax(90px, 0.55fr) minmax(92px, 0.55fr) minmax(120px, 0.7fr) minmax(220px, 1fr);
}

.record-table__row--subscriptions {
    grid-template-columns: minmax(150px, 0.9fr) minmax(170px, 1fr) minmax(90px, 0.55fr) minmax(92px, 0.55fr) minmax(140px, 0.8fr) minmax(220px, 1fr);
}

.row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.row-actions--wide {
    gap: 0.6rem;
}

.mono {
    min-width: 0;
    overflow-wrap: anywhere;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.78rem;
}

.muted {
    color: var(--rw-muted);
}

.event-list {
    display: grid;
    gap: 0.6rem;
}

.event-item {
    border: 1px solid var(--rw-border);
    border-radius: 0.55rem;
    background: var(--rw-surface-soft);
}

.event-item summary {
    display: grid;
    grid-template-columns: minmax(160px, 0.9fr) minmax(180px, 1fr) minmax(120px, 0.6fr);
    gap: 0.75rem;
    align-items: center;
    padding: 0.75rem 0.85rem;
    cursor: pointer;
}

.event-item__type {
    font-weight: 700;
    color: var(--rw-ink);
}

.event-item pre {
    margin: 0;
    padding: 0.85rem;
    border-top: 1px solid var(--rw-border);
    overflow: auto;
    color: var(--rw-muted);
    font-size: 0.76rem;
}

.generated-form {
    display: grid;
    gap: 1rem;
}

.generated-form__preview {
    min-height: 74px;
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px dashed var(--rw-border);
    border-radius: 0.75rem;
    background: color-mix(in srgb, var(--rw-surface) 88%, #00c88c);
}

.generated-form__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.field-list {
    display: grid;
    gap: 0.45rem;
    max-height: 360px;
    overflow: auto;
}

.field-row {
    display: grid;
    grid-template-columns: minmax(120px, 0.42fr) minmax(0, 1fr);
    gap: 0.75rem;
    align-items: start;
    padding: 0.55rem 0.65rem;
    border: 1px solid var(--rw-border);
    border-radius: 0.55rem;
    background: var(--rw-surface-soft);
}

.field-row__name {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--rw-ink);
}

.field-row__value {
    min-width: 0;
    overflow-wrap: anywhere;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.76rem;
    color: var(--rw-muted);
}

:deep(.paygate-button) {
    white-space: nowrap;
}

@media (max-width: 980px) {
    .payfast-test-grid,
    .subscriptions-grid {
        grid-template-columns: 1fr;
    }

    .detail-grid,
    .action-form-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 680px) {
    .form-grid,
    .field-row,
    .detail-grid,
    .action-form-grid {
        grid-template-columns: 1fr;
    }

    .event-item summary {
        grid-template-columns: 1fr;
    }
}
</style>
