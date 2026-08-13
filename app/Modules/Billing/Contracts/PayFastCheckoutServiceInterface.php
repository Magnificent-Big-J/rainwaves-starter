<?php

namespace App\Modules\Billing\Contracts;

use App\Modules\Billing\Models\Subscription;

interface PayFastCheckoutServiceInterface
{
    public function initiateOneTimePayment(array $data, ?int $userId = null): array;

    public function initiateSubscriptionPayment(array $data, ?int $userId = null): array;

    public function processItn(array $payload, string $rawBody = ''): array;

    /**
     * Cancel a subscription via PayFast's native subscription API and record the
     * outcome. Does not check ownership — callers must authorize before calling this.
     *
     * @return array{successful: bool, provider_message: ?string}
     */
    public function cancelSubscription(Subscription $subscription): array;
}
