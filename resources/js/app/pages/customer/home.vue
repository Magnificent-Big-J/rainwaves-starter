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

        <div class="customer-home__hero">
            <AppSectionCard title="Welcome back" subtitle="Use this shell for storefront accounts, member portals, and subscription products.">
                <p class="customer-home__copy">
                    Customer-facing apps should not inherit the dense admin sidebar. This shell gives you a lighter authenticated surface while still reusing the starter’s notifications, billing primitives, and profile flows.
                </p>
                <div class="customer-home__actions">
                    <v-btn color="primary" to="/auth/profile">Manage profile</v-btn>
                    <v-btn variant="outlined">View orders</v-btn>
                </div>
            </AppSectionCard>

            <SubscriptionStatusCard
                title="Customer subscription"
                subtitle="Example starter-facing subscription summary"
                amount="R 299.00 / month"
                plan="Starter Growth"
                status="active"
                billing-date="01 May 2026"
                cycles="0"
            />
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
                label="Billing"
                value="PayFast"
                helper="Starter payment integration available"
                icon="mdi-credit-card-outline"
                status="active"
            />
        </div>

        <PaymentEventList
            title="Account activity"
            subtitle="Customer event feed example for orders, renewals, refunds, and account updates."
            :events="events"
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
import AppPageHeader from '../../components/AppPageHeader.vue';
import AppSectionCard from '../../components/AppSectionCard.vue';
import AppStatCard from '../../components/AppStatCard.vue';
import AppStatusBadge from '../../components/AppStatusBadge.vue';
import PaymentEventList from '../../components/PaymentEventList.vue';
import SubscriptionStatusCard from '../../components/SubscriptionStatusCard.vue';
import { useNotificationsStore } from '../../stores/notifications';

const notifications = useNotificationsStore();

const events = [
    {
        id: 1,
        title: 'Subscription renewed',
        time: 'Today, 08:15',
        text: 'Your monthly plan renewed successfully through PayFast.',
        type: 'success',
    },
    {
        id: 2,
        title: 'Security reminder',
        time: 'Yesterday',
        text: 'Set up an authenticator app to strengthen account recovery and sign-in protection.',
        type: 'info',
    },
    {
        id: 3,
        title: 'Welcome journey',
        time: 'Earlier this week',
        text: 'This customer shell is ready for orders, invoices, subscriptions, bookings, or member content.',
        type: 'warning',
    },
];
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
