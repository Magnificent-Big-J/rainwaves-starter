<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Payment;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\PayFastCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use rainwaves\PayfastPayment\Model\Frequency;
use rainwaves\PayfastPayment\Support\PayFastSignatureHelper;
use Tests\TestCase;

class PayFastV2CompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payfast.merchant_id', '10000100');
        config()->set('payfast.merchant_key', '46f0cd694581a');
        config()->set('payfast.env', 'local');
        config()->set('payfast.return_url', 'https://starter.test/payments/payfast/return');
        config()->set('payfast.cancel_url', 'https://starter.test/payments/payfast/cancel');
        config()->set('payfast.notify_url', 'https://starter.test/payments/payfast/itn');
        config()->set('payfast.pass_phrase', 'secret');
    }

    public function test_one_time_checkout_still_generates_a_stored_pay_fast_form(): void
    {
        $result = $this->service()->initiateOneTimePayment('starter-onetime', [
            'email_address' => 'customer@rainwaves.test',
            'm_payment_id' => 'RW-1001',
        ]);

        $this->assertSame('RW-1001', $result['merchant_payment_id']);
        $this->assertStringContainsString('https://sandbox.payfast.co.za/eng/process', $result['html']);
        $this->assertStringContainsString('name="m_payment_id" value="RW-1001"', $result['html']);
        $this->assertStringContainsString('name="signature"', $result['html']);

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-1001',
            'provider' => 'payfast',
            'status' => PaymentStatus::Initiated->value,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'provider' => 'payfast',
            'event_type' => 'payment_initiated',
        ]);
    }

    public function test_subscription_checkout_still_generates_a_stored_pay_fast_form(): void
    {
        $result = $this->service()->initiateSubscriptionPayment('starter-monthly', [
            'billing_date' => '2026-08-01',
            'cycles' => 0,
            'email_address' => 'customer@rainwaves.test',
            'm_payment_id' => 'SUB-1001',
        ]);

        $this->assertSame('SUB-1001', $result['merchant_payment_id']);
        $this->assertStringContainsString('https://sandbox.payfast.co.za/eng/process', $result['html']);
        $this->assertStringContainsString('name="subscription_type" value="1"', $result['html']);
        $this->assertStringContainsString('name="signature"', $result['html']);

        $this->assertDatabaseHas('subscriptions', [
            'merchant_payment_id' => 'SUB-1001',
            'provider' => 'payfast',
            'status' => SubscriptionStatus::Initiated->value,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'provider' => 'payfast',
            'event_type' => 'subscription_initiated',
        ]);
    }

    public function test_signed_payment_itn_still_updates_a_stored_payment(): void
    {
        Payment::query()->create([
            'merchant_payment_id' => 'RW-ITN-1001',
            'provider' => 'payfast',
            'item_name' => 'Starter Plan',
            'amount_requested' => 150.00,
            'status' => PaymentStatus::Initiated,
            'initiated_at' => now(),
        ]);

        $payload = $this->signedItnPayload([
            'merchant_id' => '10000100',
            'm_payment_id' => 'RW-ITN-1001',
            'pf_payment_id' => '987654',
            'amount_gross' => '150.00',
            'amount_fee' => '3.45',
            'amount_net' => '146.55',
            'item_name' => 'Starter Plan',
            'payment_status' => 'COMPLETE',
            'email_address' => 'customer@rainwaves.test',
        ]);

        $result = $this->service()->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        $this->assertSame([
            'accepted' => true,
            'duplicate' => false,
            'type' => 'payment',
        ], array_intersect_key($result, array_flip(['accepted', 'duplicate', 'type'])));

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-ITN-1001',
            'payfast_payment_id' => '987654',
            'payment_status' => 'COMPLETE',
            'status' => PaymentStatus::Paid->value,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'provider' => 'payfast',
            'event_type' => 'itn_received',
            'event_ref' => '987654',
        ]);
    }

    public function test_browser_test_records_and_simulated_itn_expose_local_state(): void
    {
        $this->service()->initiateOneTimePayment('starter-onetime', [
            'email_address' => 'customer@rainwaves.test',
            'm_payment_id' => 'RW-BROWSER-VIEW',
        ]);

        $paymentId = Payment::query()
            ->where('merchant_payment_id', 'RW-BROWSER-VIEW')
            ->value('id');

        $this->getJson('/payments/payfast/records')
            ->assertOk()
            ->assertJsonPath('payments.0.merchant_payment_id', 'RW-BROWSER-VIEW')
            ->assertJsonPath('payments.0.status', PaymentStatus::Initiated->value)
            ->assertJsonPath('events.0.event_type', 'payment_initiated');

        $this->postJson('/payments/payfast/simulate-itn', [
            'type' => 'payment',
            'id' => $paymentId,
            'payment_status' => 'COMPLETE',
        ])
            ->assertOk()
            ->assertJsonPath('result.accepted', true)
            ->assertJsonPath('result.type', 'payment')
            ->assertJsonPath('result.status', PaymentStatus::Paid->value);

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-BROWSER-VIEW',
            'payment_status' => 'COMPLETE',
            'status' => PaymentStatus::Paid->value,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'event_type' => 'itn_received',
        ]);
    }

    public function test_raw_pay_fast_post_order_itn_updates_stored_payment(): void
    {
        Payment::query()->create([
            'merchant_payment_id' => 'RW-RAW-ITN',
            'provider' => 'payfast',
            'item_name' => 'Raw ITN Plan',
            'amount_requested' => 175.00,
            'status' => PaymentStatus::Initiated,
            'initiated_at' => now(),
        ]);

        $rawBody = 'm_payment_id=RW-RAW-ITN&pf_payment_id=PF-RAW-1001&payment_status=COMPLETE&item_name=Raw+ITN+Plan&amount_gross=175.00&amount_fee=2.30&amount_net=172.70&merchant_id=10000100&email_address=customer%40rainwaves.test';
        $signature = md5($rawBody.'&passphrase='.urlencode('secret'));
        parse_str($rawBody.'&signature='.$signature, $payload);

        $result = $this->service()->processItn($payload, $rawBody.'&signature='.$signature);

        $this->assertTrue($result['accepted']);
        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-RAW-ITN',
            'payfast_payment_id' => 'PF-RAW-1001',
            'status' => PaymentStatus::Paid->value,
        ]);
    }

    public function test_return_and_cancel_are_cosmetic_redirects_that_cannot_mutate_state(): void
    {
        Payment::query()->create([
            'merchant_payment_id' => 'RW-RETURN-CANCEL',
            'provider' => 'payfast',
            'item_name' => 'Return Cancel Plan',
            'amount_requested' => 100.00,
            'status' => PaymentStatus::Initiated,
            'initiated_at' => now(),
        ]);

        $this->get('/payments/payfast/return?m_payment_id=RW-RETURN-CANCEL')
            ->assertRedirect('/payfast-browser-test?payfast_result=return&m_payment_id=RW-RETURN-CANCEL');

        // A buyer freely controls this URL (wrong id, replayed, forged token) — it must
        // never be able to move a payment out of the state the signed ITN call put it in.
        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-RETURN-CANCEL',
            'status' => PaymentStatus::Initiated->value,
        ]);

        $this->get('/payments/payfast/cancel?m_payment_id=RW-RETURN-CANCEL')
            ->assertRedirect('/payfast-browser-test?payfast_result=cancel&m_payment_id=RW-RETURN-CANCEL');

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-RETURN-CANCEL',
            'status' => PaymentStatus::Initiated->value,
        ]);
    }

    public function test_return_and_cancel_cannot_mutate_state_for_an_unrelated_payment_id(): void
    {
        Payment::query()->create([
            'merchant_payment_id' => 'RW-REAL-PAYMENT',
            'provider' => 'payfast',
            'item_name' => 'Real Plan',
            'amount_requested' => 500.00,
            'status' => PaymentStatus::Initiated,
            'initiated_at' => now(),
        ]);

        // Forged/guessed id — the endpoint doesn't even look the record up, so this is
        // simply proving there's nothing here for an attacker to target.
        $this->get('/payments/payfast/return?m_payment_id=RW-DOES-NOT-EXIST')
            ->assertRedirect('/payfast-browser-test?payfast_result=return&m_payment_id=RW-DOES-NOT-EXIST');

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-REAL-PAYMENT',
            'status' => PaymentStatus::Initiated->value,
        ]);
    }

    public function test_return_redirects_to_dashboard_outside_local_and_testing(): void
    {
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->get('/payments/payfast/return?m_payment_id=RW-ANY')
                ->assertRedirect('/dashboard?payfast_result=return&m_payment_id=RW-ANY');
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_subscription_native_actions_require_token_and_record_card_update_link(): void
    {
        $subscription = Subscription::query()->create([
            'merchant_payment_id' => 'SUB-ACTION-1001',
            'provider' => 'payfast',
            'item_name' => 'Action Subscription',
            'amount_requested' => 99.00,
            'recurring_amount' => 99.00,
            'billing_date' => '2026-08-01',
            'frequency' => Frequency::MONTHLY,
            'cycles' => 0,
            'status' => SubscriptionStatus::Active,
            'initiated_at' => now(),
        ]);

        $this->postJson('/payments/payfast/subscriptions/action', [
            'id' => $subscription->id,
            'action' => 'fetch',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'A PayFast subscription token is required before native subscription actions can run.');

        $subscription->forceFill(['token' => 'sub-token-1001'])->save();

        $this->postJson('/payments/payfast/subscriptions/action', [
            'id' => $subscription->id,
            'action' => 'card_update_link',
        ])
            ->assertOk()
            ->assertJsonPath('result.successful', true)
            ->assertJsonPath('result.status_code', 200)
            ->assertJsonPath('result.url', 'https://sandbox.payfast.co.za/eng/recurring/update/sub-token-1001?return=https%3A%2F%2Fstarter.test%2Fpayments%2Fpayfast%2Freturn');

        $this->assertDatabaseHas('payment_events', [
            'subscription_id' => $subscription->id,
            'provider' => 'payfast',
            'event_type' => 'subscription_action_card_update_link',
        ]);
    }

    private function service(): PayFastCheckoutService
    {
        return app(PayFastCheckoutService::class);
    }

    private function signedItnPayload(array $payload): array
    {
        $signatureFields = [...$payload, 'passphrase' => 'secret'];
        ksort($signatureFields);

        $payload['signature'] = md5(PayFastSignatureHelper::buildQuery($signatureFields));

        return $payload;
    }
}
