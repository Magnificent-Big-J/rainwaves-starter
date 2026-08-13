<template>
    <div class="customer-home">
        <AppPageHeader
            eyebrow="Customer space"
            title="Your account hub"
            subtitle="This surface stays closer to the public experience while still giving signed-in customers notifications, account access, billing visibility, and product-specific actions."
        >
            <template #metrics>
                <AppStatusBadge status="active" label="Customer layout live" />
                <AppStatusBadge status="processing" label="Commercial starter surface" />
            </template>
        </AppPageHeader>

        <AppBanner
            title="Customer surface baseline"
            message="This shell now follows the same sectional rhythm as the admin pages while staying lighter and more customer-facing in tone."
            type="info"
        />

        <div class="customer-home__hero">
            <AppSectionCard
                title="Welcome back"
                subtitle="Use this shell for storefront accounts, member portals, and subscription products."
            >
                <p class="customer-home__copy">
                    Customer-facing apps should not inherit the dense admin sidebar. This shell gives you a lighter
                    authenticated surface while still reusing the starter’s notifications, billing primitives, and
                    profile flows.
                </p>
                <div class="customer-home__actions">
                    <v-btn color="primary" to="/profile">Manage profile</v-btn>
                    <v-btn variant="outlined" to="/account/billing">View billing</v-btn>
                </div>
            </AppSectionCard>

            <template v-if="appConfig.modules.billing">
                <SubscriptionStatusCard
                    v-if="billing.latestSubscription"
                    title="Your subscription"
                    :amount="`R ${Number(billing.latestSubscription.recurring_amount).toFixed(2)} / cycle`"
                    :plan="billing.latestSubscription.item_name"
                    :status="billing.latestSubscription.status"
                    :status-label="billing.latestSubscription.status_label"
                    :billing-date="billing.latestSubscription.billing_date || 'Not scheduled'"
                    :cycles="String(billing.latestSubscription.cycles ?? 0)"
                    :terminal="billing.latestSubscription.is_terminal"
                />
                <AppSectionCard v-else title="Your subscription" subtitle="No active subscription">
                    <AppEmptyState title="No subscription yet" text="Start one from the Billing page." icon="mdi-sync">
                        <template #actions>
                            <v-btn color="primary" size="small" to="/account/billing">Go to billing</v-btn>
                        </template>
                    </AppEmptyState>
                </AppSectionCard>
            </template>
        </div>

        <div class="customer-home__grid">
            <AppStatCard
                label="Notifications"
                :value="String(notifications.unreadCount)"
                helper="Unread account and system items"
                icon="mdi-bell-badge-outline"
                status="active"
            />
            <AppStatCard
                label="Security"
                value="2FA ready"
                helper="Authenticator app and email OTP supported"
                icon="mdi-shield-lock-outline"
                status="processing"
            />
            <AppStatCard
                v-if="appConfig.modules.billing"
                label="Billing"
                :value="billing.latestPayment ? billing.latestPayment.status_label : 'No payments'"
                helper="PayFast payment integration"
                icon="mdi-credit-card-outline"
                :status="billing.latestPayment ? billing.latestPayment.status : 'inactive'"
            />
        </div>

        <PaymentEventList
            v-if="appConfig.modules.billing"
            title="Account activity"
            subtitle="Real payment and subscription events from PayFast ITN callbacks."
            :events="timelineEvents"
        />
    </div>
</template>

<route lang="json">
{
    "meta": {
        "layout": "customer",
        "title": "Customer Home",
        "requiresAuth": true
    }
}
</route>

<script setup>
import { computed, onMounted } from 'vue';

import AppBanner from '../../components/AppBanner.vue';
import AppEmptyState from '../../components/AppEmptyState.vue';
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import PaymentEventList from '../../components/PaymentEventList.vue';
import SubscriptionStatusCard from '../../components/SubscriptionStatusCard.vue';
import { useAppConfigStore } from '../../stores/app-config';
import { useBillingStore } from '../../stores/billing';
import { useNotificationsStore } from '../../stores/notifications';

const notifications = useNotificationsStore();
const appConfig = useAppConfigStore();
const billing = useBillingStore();

const timelineEvents = computed(() =>
    billing.recentEvents.map((event) => ({
        id: event.id,
        title: event.event_type,
        time: event.received_at ? new Date(event.received_at).toLocaleString() : 'Unknown',
        text: event.payment_id ? `Payment #${event.payment_id}` : `Subscription #${event.subscription_id}`,
    }))
);

onMounted(() => {
    if (appConfig.modules.billing) {
        billing.fetch();
    }
});
</script>

<style scoped>
.customer-home {
    display: grid;
    gap: 1.5rem;
}

.customer-home__hero {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1.25fr 0.95fr;
}

.customer-home__copy {
    margin: 0;
    color: var(--rw-dim);
    line-height: 1.7;
}

.customer-home__actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.1rem;
    flex-wrap: wrap;
}

.customer-home__grid {
    display: grid;
    gap: 0.9rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 960px) {
    .customer-home__hero,
    .customer-home__grid {
        grid-template-columns: 1fr;
    }
}
</style>
