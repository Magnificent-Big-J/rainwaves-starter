<?php

namespace Tests\Feature\Api;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Billing\Models\Payment;
use App\Modules\Billing\Models\PaymentEvent;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_billing_summary_is_empty_for_a_user_with_no_payments(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)
            ->getJson('/api/v1/billing')
            ->assertOk()
            ->assertJsonPath('data.latest_payment', null)
            ->assertJsonPath('data.latest_subscription', null)
            ->assertJsonCount(0, 'data.recent_events');
    }

    public function test_billing_summary_returns_only_the_authenticated_users_own_records(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $ownPayment = Payment::query()->create([
            'user_id' => $customer->id,
            'merchant_payment_id' => 'RW-MINE',
            'provider' => 'payfast',
            'item_name' => 'My Plan',
            'amount_requested' => 199.00,
            'status' => PaymentStatus::Paid,
            'initiated_at' => now(),
            'paid_at' => now(),
        ]);

        Payment::query()->create([
            'user_id' => $owner->id,
            'merchant_payment_id' => 'RW-NOT-MINE',
            'provider' => 'payfast',
            'item_name' => 'Someone Else Plan',
            'amount_requested' => 500.00,
            'status' => PaymentStatus::Paid,
            'initiated_at' => now(),
        ]);

        PaymentEvent::query()->create([
            'payment_id' => $ownPayment->id,
            'provider' => 'payfast',
            'event_type' => 'itn_received',
            'event_ref' => 'evt-mine',
            'payload' => ['payment_status' => 'COMPLETE'],
            'received_at' => now(),
        ]);

        $response = $this->actingAs($customer)->getJson('/api/v1/billing');

        $response->assertOk()
            ->assertJsonPath('data.latest_payment.merchant_payment_id', 'RW-MINE')
            ->assertJsonPath('data.latest_payment.status', 'paid')
            ->assertJsonCount(1, 'data.recent_events');
    }

    public function test_billing_summary_prefers_an_active_subscription_over_a_more_recent_cancelled_one(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        Subscription::query()->create([
            'user_id' => $customer->id,
            'merchant_payment_id' => 'SUB-OLD-ACTIVE',
            'provider' => 'payfast',
            'item_name' => 'Growth plan',
            'amount_requested' => 99.00,
            'recurring_amount' => 99.00,
            'billing_date' => now()->addMonth(),
            'frequency' => 3,
            'cycles' => 0,
            'status' => SubscriptionStatus::Active,
            'initiated_at' => now()->subDay(),
        ]);

        Subscription::query()->create([
            'user_id' => $customer->id,
            'merchant_payment_id' => 'SUB-NEW-CANCELLED',
            'provider' => 'payfast',
            'item_name' => 'Trial plan',
            'amount_requested' => 0,
            'recurring_amount' => 0,
            'billing_date' => now(),
            'frequency' => 3,
            'cycles' => 0,
            'status' => SubscriptionStatus::Cancelled,
            'initiated_at' => now(),
        ]);

        $this->actingAs($customer)
            ->getJson('/api/v1/billing')
            ->assertOk()
            ->assertJsonPath('data.latest_subscription.merchant_payment_id', 'SUB-OLD-ACTIVE')
            ->assertJsonPath('data.latest_subscription.status', 'active');
    }
}
