<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Subscription;
use App\Services\PayFastCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use rainwaves\PayfastPayment\Support\PayFastSignatureHelper;
use Tests\TestCase;

/**
 * RS-001 evidence: ITN is the only path that can move payment/subscription state, so it
 * has to survive a hostile or unreliable webhook sender — forged signatures, a wrong
 * merchant id, a tampered amount, retried/duplicate deliveries, and notifications that
 * arrive late/out of order relative to a final state that already landed.
 */
class PayFastItnHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payfast.merchant_id', '10000100');
        config()->set('payfast.pass_phrase', 'secret');
    }

    public function test_invalid_signature_is_rejected_and_does_not_touch_state(): void
    {
        $payment = $this->makePayment('RW-BAD-SIG', PaymentStatus::Initiated);

        $payload = $this->paymentPayload($payment, 'COMPLETE');
        $payload['signature'] = 'not-a-real-signature';

        $result = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        $this->assertSame(['accepted' => false, 'reason' => 'invalid_signature'], array_intersect_key($result, array_flip(['accepted', 'reason'])));
        $this->assertDatabaseHas('payments', ['merchant_payment_id' => 'RW-BAD-SIG', 'status' => PaymentStatus::Initiated->value]);
    }

    public function test_wrong_merchant_id_is_rejected(): void
    {
        $payment = $this->makePayment('RW-WRONG-MERCHANT', PaymentStatus::Initiated);

        $payload = $this->signedPayload($this->paymentPayload($payment, 'COMPLETE', merchantId: '99999999'));

        $result = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        $this->assertFalse($result['accepted']);
        $this->assertSame('merchant_id_mismatch', $result['reason']);
        $this->assertDatabaseHas('payments', ['merchant_payment_id' => 'RW-WRONG-MERCHANT', 'status' => PaymentStatus::Initiated->value]);
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        $payment = $this->makePayment('RW-WRONG-AMOUNT', PaymentStatus::Initiated, amount: 150.00);

        $payload = $this->signedPayload($this->paymentPayload($payment, 'COMPLETE', amountGross: '1.00'));

        $result = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        $this->assertFalse($result['accepted']);
        $this->assertSame('amount_mismatch', $result['reason']);
        $this->assertDatabaseHas('payments', ['merchant_payment_id' => 'RW-WRONG-AMOUNT', 'status' => PaymentStatus::Initiated->value]);
    }

    public function test_duplicate_itn_is_accepted_but_does_not_reapply(): void
    {
        $payment = $this->makePayment('RW-DUP', PaymentStatus::Initiated);
        $payload = $this->signedPayload($this->paymentPayload($payment, 'COMPLETE'));

        $first = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));
        $second = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        $this->assertFalse($first['duplicate']);
        $this->assertTrue($second['duplicate']);
        $this->assertTrue($second['accepted']);

        $this->assertSame(
            1,
            PaymentEvent::query()->where('event_type', 'itn_received')->where('payment_id', $payment->id)->count(),
            'A replayed ITN must not create a second processed event.'
        );
    }

    public function test_delayed_pending_itn_after_complete_cannot_regress_a_paid_payment(): void
    {
        $payment = $this->makePayment('RW-DELAYED', PaymentStatus::Initiated);

        $complete = $this->signedPayload($this->paymentPayload($payment, 'COMPLETE', pfPaymentId: 'PF-FIRST'));
        $this->service()->processItn($complete, PayFastSignatureHelper::buildQuery($complete));

        $this->assertDatabaseHas('payments', ['merchant_payment_id' => 'RW-DELAYED', 'status' => PaymentStatus::Paid->value]);

        // A different, later-arriving notification for the same payment (distinct
        // pf_payment_id, as PayFast retries can carry) reporting an earlier state.
        $delayedPending = $this->signedPayload($this->paymentPayload($payment, 'PENDING', pfPaymentId: 'PF-SECOND'));
        $result = $this->service()->processItn($delayedPending, PayFastSignatureHelper::buildQuery($delayedPending));

        $this->assertFalse($result['accepted']);
        $this->assertSame('stale_itn_after_final_status', $result['reason']);
        $this->assertDatabaseHas('payments', ['merchant_payment_id' => 'RW-DELAYED', 'status' => PaymentStatus::Paid->value]);
    }

    public function test_subscription_can_still_transition_from_active_to_cancelled(): void
    {
        $subscription = $this->makeSubscription('SUB-ACTIVE-CANCEL', SubscriptionStatus::Active);

        $payload = $this->signedPayload($this->subscriptionPayload($subscription, 'CANCELLED'));
        $result = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        $this->assertTrue($result['accepted']);
        $this->assertDatabaseHas('subscriptions', ['merchant_payment_id' => 'SUB-ACTIVE-CANCEL', 'status' => SubscriptionStatus::Cancelled->value]);
    }

    public function test_delayed_itn_after_subscription_cancelled_cannot_reactivate_it(): void
    {
        $subscription = $this->makeSubscription('SUB-DELAYED', SubscriptionStatus::Cancelled);

        $payload = $this->signedPayload($this->subscriptionPayload($subscription, 'COMPLETE'));
        $result = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        $this->assertFalse($result['accepted']);
        $this->assertSame('stale_itn_after_final_status', $result['reason']);
        $this->assertDatabaseHas('subscriptions', ['merchant_payment_id' => 'SUB-DELAYED', 'status' => SubscriptionStatus::Cancelled->value]);
    }

    private function service(): PayFastCheckoutService
    {
        return app(PayFastCheckoutService::class);
    }

    private function makePayment(string $merchantPaymentId, PaymentStatus $status, float $amount = 150.00): Payment
    {
        return Payment::query()->create([
            'merchant_payment_id' => $merchantPaymentId,
            'provider' => 'payfast',
            'item_name' => 'Hardening Test Plan',
            'amount_requested' => $amount,
            'status' => $status,
            'initiated_at' => now(),
        ]);
    }

    private function makeSubscription(string $merchantPaymentId, SubscriptionStatus $status): Subscription
    {
        return Subscription::query()->create([
            'merchant_payment_id' => $merchantPaymentId,
            'provider' => 'payfast',
            'token' => 'token-'.$merchantPaymentId,
            'item_name' => 'Hardening Test Subscription',
            'amount_requested' => 99.00,
            'recurring_amount' => 99.00,
            'billing_date' => now()->addMonth()->toDateString(),
            'frequency' => 3,
            'cycles' => 0,
            'status' => $status,
            'initiated_at' => now(),
        ]);
    }

    private function paymentPayload(Payment $payment, string $status, ?string $merchantId = null, ?string $amountGross = null, ?string $pfPaymentId = null): array
    {
        return [
            'merchant_id' => $merchantId ?? '10000100',
            'm_payment_id' => $payment->merchant_payment_id,
            'pf_payment_id' => $pfPaymentId ?? 'PF-'.$payment->id,
            'payment_status' => $status,
            'item_name' => $payment->item_name,
            'amount_gross' => $amountGross ?? number_format((float) $payment->amount_requested, 2, '.', ''),
            'amount_fee' => '0.00',
            'amount_net' => $amountGross ?? number_format((float) $payment->amount_requested, 2, '.', ''),
            'email_address' => 'customer@rainwaves.test',
        ];
    }

    private function subscriptionPayload(Subscription $subscription, string $status): array
    {
        return [
            'merchant_id' => '10000100',
            'm_payment_id' => $subscription->merchant_payment_id,
            'token' => $subscription->token,
            'pf_payment_id' => 'PF-'.$subscription->id,
            'payment_status' => $status,
            'item_name' => $subscription->item_name,
            'amount_gross' => number_format((float) $subscription->amount_requested, 2, '.', ''),
            'amount_fee' => '0.00',
            'amount_net' => number_format((float) $subscription->amount_requested, 2, '.', ''),
            'billing_date' => $subscription->billing_date,
            'email_address' => 'customer@rainwaves.test',
        ];
    }

    private function signedPayload(array $payload): array
    {
        $signatureFields = [...$payload, 'passphrase' => 'secret'];
        ksort($signatureFields);

        $payload['signature'] = md5(PayFastSignatureHelper::buildQuery($signatureFields));

        return $payload;
    }
}
