<?php

namespace App\Modules\Billing\Services;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Modules\Billing\Contracts\PayFastCheckoutServiceInterface;
use App\Modules\Billing\Models\Payment;
use App\Modules\Billing\Models\PaymentEvent;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use rainwaves\PayfastPayment\Client\PayFastClient;
use rainwaves\PayfastPayment\Itn\PayFastItnValidator;
use rainwaves\PayfastPayment\Model\Frequency;
use rainwaves\PayfastPayment\PayFast;
use rainwaves\PayfastPayment\PayFastSubscription;

class PayFastCheckoutService implements PayFastCheckoutServiceInterface
{
    public function initiateOneTimePayment(array $data, ?int $userId = null): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $merchantPaymentId = $data['m_payment_id'] ?? (string) str()->uuid();

            $payment = Payment::query()->updateOrCreate(
                ['merchant_payment_id' => $merchantPaymentId],
                [
                    'user_id' => $userId,
                    'provider' => 'payfast',
                    'item_name' => (string) $data['item_name'],
                    'item_description' => $data['item_description'] ?? null,
                    'amount_requested' => (float) $data['amount'],
                    'customer_first_name' => $data['name_first'] ?? null,
                    'customer_last_name' => $data['name_last'] ?? null,
                    'customer_email' => $data['email_address'] ?? null,
                    'status' => PaymentStatus::Initiated,
                    'initiated_at' => now(),
                    'metadata' => $data,
                ]
            );

            $input = array_merge($data, ['m_payment_id' => $merchantPaymentId]);
            $html = $this->makeOneTimePaymentForm($input);

            $this->recordEvent(
                eventType: 'payment_initiated',
                payload: $input,
                paymentId: $payment->id
            );

            return [
                'payment' => $payment,
                'merchant_payment_id' => $merchantPaymentId,
                'html' => $html,
            ];
        });
    }

    public function initiateSubscriptionPayment(array $data, ?int $userId = null): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $merchantPaymentId = $data['m_payment_id'] ?? ('sub-'.str()->uuid());
            $frequency = (int) ($data['frequency'] ?? Frequency::MONTHLY);

            $subscription = Subscription::query()->updateOrCreate(
                ['merchant_payment_id' => $merchantPaymentId],
                [
                    'user_id' => $userId,
                    'provider' => 'payfast',
                    'item_name' => (string) $data['item_name'],
                    'amount_requested' => (float) $data['amount'],
                    'recurring_amount' => (float) $data['recurring_amount'],
                    'billing_date' => $data['billing_date'],
                    'frequency' => $frequency,
                    'cycles' => (int) ($data['cycles'] ?? 0),
                    'customer_first_name' => $data['name_first'] ?? null,
                    'customer_last_name' => $data['name_last'] ?? null,
                    'customer_email' => $data['email_address'] ?? null,
                    'status' => SubscriptionStatus::Initiated,
                    'initiated_at' => now(),
                    'metadata' => $data,
                ]
            );

            $input = array_merge($data, [
                'm_payment_id' => $merchantPaymentId,
                'frequency' => $frequency,
                'cycles' => (int) ($data['cycles'] ?? 0),
                'subscription_notify_email' => true,
                'subscription_notify_webhook' => true,
                'subscription_notify_buyer' => true,
            ]);

            $html = $this->makeSubscriptionPaymentForm($input);

            $this->recordEvent(
                eventType: 'subscription_initiated',
                payload: $input,
                subscriptionId: $subscription->id
            );

            return [
                'subscription' => $subscription,
                'merchant_payment_id' => $merchantPaymentId,
                'html' => $html,
            ];
        });
    }

    public function processItn(array $payload, string $rawBody = ''): array
    {
        $validationFailure = $this->itnValidationFailure($payload, $rawBody);

        if ($validationFailure) {
            return $this->rejectItn(
                reason: $validationFailure,
                payload: $payload,
                eventRef: (string) ($payload['pf_payment_id'] ?? $payload['token'] ?? $payload['m_payment_id'] ?? str()->uuid())
            );
        }

        return DB::transaction(function () use ($payload) {
            $isSubscription = ! empty($payload['token']);
            $eventRef = (string) ($payload['pf_payment_id'] ?? $payload['token'] ?? $payload['m_payment_id'] ?? str()->uuid());
            $eventType = 'itn_received';

            if ($this->hasProcessedEvent($eventType, $eventRef)) {
                return [
                    'accepted' => true,
                    'duplicate' => true,
                    'type' => $isSubscription ? 'subscription' : 'payment',
                    'event_ref' => $eventRef,
                ];
            }

            if ($isSubscription) {
                $subscription = Subscription::query()
                    ->where('token', $payload['token'])
                    ->orWhere('merchant_payment_id', $payload['m_payment_id'] ?? '')
                    ->first();

                if (! $subscription) {
                    return $this->rejectItn(reason: 'subscription_not_found', payload: $payload, eventRef: $eventRef);
                }

                if (! $this->matchesReference($subscription->merchant_payment_id, $payload['m_payment_id'] ?? null)) {
                    return $this->rejectItn(reason: 'merchant_payment_id_mismatch', payload: $payload, eventRef: $eventRef, subscriptionId: $subscription->id);
                }

                if (! $this->matchesAmount($subscription->amount_requested, $payload['amount_gross'] ?? null)) {
                    return $this->rejectItn(reason: 'amount_mismatch', payload: $payload, eventRef: $eventRef, subscriptionId: $subscription->id);
                }

                $rawStatus = (string) ($payload['payment_status'] ?? '');
                $resolvedStatus = $this->resolveSubscriptionStatus($rawStatus);

                if ($this->isStaleSubscriptionTransition($subscription->status, $resolvedStatus)) {
                    return $this->rejectItn(reason: 'stale_itn_after_final_status', payload: $payload, eventRef: $eventRef, subscriptionId: $subscription->id);
                }

                $subscription->fill([
                    'token' => $payload['token'] ?? $subscription->token,
                    'payment_status' => $rawStatus ?: $subscription->payment_status,
                    'status' => $resolvedStatus,
                    'customer_first_name' => $payload['name_first'] ?? $subscription->customer_first_name,
                    'customer_last_name' => $payload['name_last'] ?? $subscription->customer_last_name,
                    'customer_email' => $payload['email_address'] ?? $subscription->customer_email,
                    'billing_date' => $payload['billing_date'] ?? $subscription->billing_date,
                    'activated_at' => $resolvedStatus->isActive() ? ($subscription->activated_at ?? now()) : $subscription->activated_at,
                    'metadata' => array_merge($subscription->metadata ?? [], ['last_itn' => $payload]),
                ]);
                $subscription->save();

                $this->recordEvent(eventType: $eventType, eventRef: $eventRef, payload: $payload, subscriptionId: $subscription->id);

                return ['accepted' => true, 'duplicate' => false, 'type' => 'subscription', 'id' => $subscription->id, 'status' => $subscription->status];
            }

            $payment = Payment::query()
                ->where('payfast_payment_id', (string) ($payload['pf_payment_id'] ?? ''))
                ->orWhere('merchant_payment_id', (string) ($payload['m_payment_id'] ?? ''))
                ->first();

            if (! $payment) {
                return $this->rejectItn(reason: 'payment_not_found', payload: $payload, eventRef: $eventRef);
            }

            if (! $this->matchesReference($payment->merchant_payment_id, $payload['m_payment_id'] ?? null)) {
                return $this->rejectItn(reason: 'merchant_payment_id_mismatch', payload: $payload, eventRef: $eventRef, paymentId: $payment->id);
            }

            if (! $this->matchesAmount($payment->amount_requested, $payload['amount_gross'] ?? null)) {
                return $this->rejectItn(reason: 'amount_mismatch', payload: $payload, eventRef: $eventRef, paymentId: $payment->id);
            }

            $rawStatus = (string) ($payload['payment_status'] ?? '');
            $resolvedStatus = $this->resolvePaymentStatus($rawStatus);

            if ($payment->status->isTerminal() && $resolvedStatus !== $payment->status) {
                return $this->rejectItn(reason: 'stale_itn_after_final_status', payload: $payload, eventRef: $eventRef, paymentId: $payment->id);
            }

            $payment->fill([
                'payfast_payment_id' => isset($payload['pf_payment_id']) ? (string) $payload['pf_payment_id'] : $payment->payfast_payment_id,
                'payment_status' => $rawStatus ?: $payment->payment_status,
                'status' => $resolvedStatus,
                'item_name' => $payload['item_name'] ?? $payment->item_name,
                'item_description' => $payload['item_description'] ?? $payment->item_description,
                'amount_gross' => $this->toDecimalOrNull($payload['amount_gross'] ?? null),
                'amount_fee' => $this->toDecimalOrNull($payload['amount_fee'] ?? null),
                'amount_net' => $this->toDecimalOrNull($payload['amount_net'] ?? null),
                'customer_first_name' => $payload['name_first'] ?? $payment->customer_first_name,
                'customer_last_name' => $payload['name_last'] ?? $payment->customer_last_name,
                'customer_email' => $payload['email_address'] ?? $payment->customer_email,
                'paid_at' => $resolvedStatus === PaymentStatus::Paid ? ($payment->paid_at ?? now()) : $payment->paid_at,
                'metadata' => array_merge($payment->metadata ?? [], ['last_itn' => $payload]),
            ]);
            $payment->save();

            $this->recordEvent(eventType: $eventType, eventRef: $eventRef, payload: $payload, paymentId: $payment->id);

            return ['accepted' => true, 'duplicate' => false, 'type' => 'payment', 'id' => $payment->id, 'status' => $payment->status];
        });
    }

    public function cancelSubscription(Subscription $subscription): array
    {
        $client = PayFastClient::make($this->payFastConfig())->subscriptions();
        $result = $client->cancel((string) $subscription->token);

        $this->recordEvent(
            eventType: 'subscription_action_cancel',
            payload: [
                'successful' => $result->successful(),
                'status_code' => $result->statusCode(),
                'provider_message' => $result->providerMessage(),
            ],
            subscriptionId: $subscription->id,
            eventRef: $subscription->token.':cancel:'.Str::uuid(),
        );

        if ($result->successful()) {
            $subscription->forceFill([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();
        }

        return [
            'successful' => $result->successful(),
            'provider_message' => $result->providerMessage(),
        ];
    }

    private function makeOneTimePaymentForm(array $input): string
    {
        $client = new PayFast($this->payFastConfig());

        return $client->makePaymentWithAForm($input)->createForm();
    }

    private function makeSubscriptionPaymentForm(array $input): string
    {
        $client = new PayFastSubscription($this->payFastConfig());

        return $client->createSubscriptionWithAForm($input)->createForm();
    }

    private function payFastConfig(): array
    {
        return [
            'merchant_id' => config('payfast.merchant_id'),
            'merchant_key' => config('payfast.merchant_key'),
            'env' => config('payfast.env'),
            'return_url' => config('payfast.return_url'),
            'cancel_url' => config('payfast.cancel_url'),
            'notify_url' => config('payfast.notify_url'),
            'pass_phrase' => config('payfast.pass_phrase'),
        ];
    }

    private function resolvePaymentStatus(string $payfastStatus): PaymentStatus
    {
        return match (strtoupper($payfastStatus)) {
            'COMPLETE' => PaymentStatus::Paid,
            'FAILED' => PaymentStatus::Failed,
            'CANCELLED' => PaymentStatus::Cancelled,
            'PENDING' => PaymentStatus::Pending,
            default => PaymentStatus::Processing,
        };
    }

    private function resolveSubscriptionStatus(string $payfastStatus): SubscriptionStatus
    {
        return match (strtoupper($payfastStatus)) {
            'COMPLETE' => SubscriptionStatus::Active,
            'FAILED' => SubscriptionStatus::Failed,
            'CANCELLED' => SubscriptionStatus::Cancelled,
            'PENDING' => SubscriptionStatus::Pending,
            default => SubscriptionStatus::Processing,
        };
    }

    /**
     * A delayed or out-of-order ITN must never regress a payment/subscription out of a
     * final state (e.g. a late PENDING notification arriving after COMPLETE already
     * landed). Subscription::isTerminal() also reports Active as terminal — that's
     * correct for "no automatic housekeeping needed" but wrong here, since an Active
     * subscription legitimately still transitions to Cancelled/Failed later. So this
     * only treats Cancelled/Failed as final for subscriptions.
     */
    private function isStaleSubscriptionTransition(SubscriptionStatus $current, SubscriptionStatus $incoming): bool
    {
        $final = [SubscriptionStatus::Cancelled, SubscriptionStatus::Failed];

        return in_array($current, $final, true) && $incoming !== $current;
    }

    private function itnValidationFailure(array $payload, string $rawBody): ?string
    {
        $hasReference = ! empty($payload['m_payment_id'])
            || ! empty($payload['pf_payment_id'])
            || ! empty($payload['token']);

        if (! $hasReference || empty($payload['payment_status'])) {
            return 'missing_required_itn_fields';
        }

        $validator = new PayFastItnValidator(
            $payload,
            (string) config('payfast.pass_phrase', ''),
            $rawBody
        );

        if (! $validator->validateSignature()) {
            return 'invalid_signature';
        }

        if (! $validator->validateMerchantId((string) config('payfast.merchant_id'))) {
            return 'merchant_id_mismatch';
        }

        return null;
    }

    private function toDecimalOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function recordEvent(
        string $eventType,
        array $payload,
        ?int $paymentId = null,
        ?int $subscriptionId = null,
        ?string $eventRef = null
    ): bool {
        $attributes = [
            'payment_id' => $paymentId,
            'subscription_id' => $subscriptionId,
            'provider' => 'payfast',
            'event_type' => $eventType,
            'event_ref' => $eventRef,
        ];

        $values = ['payload' => $payload, 'received_at' => now()];

        if ($eventRef) {
            $event = PaymentEvent::query()->firstOrCreate(
                ['provider' => 'payfast', 'event_type' => $eventType, 'event_ref' => $eventRef],
                [...$attributes, ...$values]
            );

            return $event->wasRecentlyCreated;
        }

        PaymentEvent::query()->create([...$attributes, ...$values]);

        return true;
    }

    private function hasProcessedEvent(string $eventType, string $eventRef): bool
    {
        return PaymentEvent::query()
            ->where('provider', 'payfast')
            ->where('event_type', $eventType)
            ->where('event_ref', $eventRef)
            ->exists();
    }

    private function matchesReference(string $expected, mixed $incoming): bool
    {
        if (! $incoming) {
            return true;
        }

        return (string) $incoming === $expected;
    }

    private function matchesAmount(mixed $expected, mixed $incoming): bool
    {
        $incomingAmount = $this->toDecimalOrNull($incoming);

        if ($incomingAmount === null) {
            return false;
        }

        return abs(((float) $expected) - $incomingAmount) < 0.01;
    }

    private function rejectItn(
        string $reason,
        array $payload,
        string $eventRef,
        ?int $paymentId = null,
        ?int $subscriptionId = null
    ): array {
        $this->recordEvent(
            eventType: 'itn_rejected',
            eventRef: $eventRef,
            payload: [...$payload, '_reject_reason' => $reason],
            paymentId: $paymentId,
            subscriptionId: $subscriptionId
        );

        return ['accepted' => false, 'duplicate' => false, 'reason' => $reason, 'event_ref' => $eventRef];
    }
}
