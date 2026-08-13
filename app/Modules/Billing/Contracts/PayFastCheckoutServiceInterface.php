<?php

namespace App\Modules\Billing\Contracts;

use App\Modules\Billing\Models\Subscription;

interface PayFastCheckoutServiceInterface
{
    /**
     * $plan is a key into config('billing-plans') — item_name/amount are resolved
     * from there, never trusted from client input. $customerData carries only
     * contact/reference fields: name_first, name_last, email_address, m_payment_id.
     *
     * @throws \InvalidArgumentException if $plan doesn't exist or isn't a 'payment'-mode plan
     */
    public function initiateOneTimePayment(string $plan, array $customerData, ?int $userId = null): array;

    /**
     * Same plan-resolution contract as initiateOneTimePayment(). $customerData also
     * carries billing_date (required) and cycles (optional, default 0 = ongoing) —
     * scheduling choices, not pricing, so they stay caller-supplied.
     *
     * @throws \InvalidArgumentException if $plan doesn't exist or isn't a 'subscription'-mode plan
     */
    public function initiateSubscriptionPayment(string $plan, array $customerData, ?int $userId = null): array;

    public function processItn(array $payload, string $rawBody = ''): array;

    /**
     * Cancel a subscription via PayFast's native subscription API and record the
     * outcome. Does not check ownership — callers must authorize before calling this.
     *
     * @return array{successful: bool, provider_message: ?string}
     */
    public function cancelSubscription(Subscription $subscription): array;
}
