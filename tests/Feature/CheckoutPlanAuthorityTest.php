<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Billing\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RS-002 (scoped): checkout no longer trusts a client-supplied amount/item_name —
 * the client references a plan key from config('billing-plans'), and the real
 * price/description is resolved server-side. These tests exist because, before
 * this change, nothing exercised the FormRequest/controller layer for checkout
 * initiation at all — PayFastV2CompatibilityTest only ever called the service
 * directly, bypassing validation and the ownership/admin-override logic entirely.
 */
class CheckoutPlanAuthorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('payfast.merchant_id', '10000100');
        config()->set('payfast.merchant_key', '46f0cd694581a');
        config()->set('payfast.env', 'local');
        config()->set('payfast.pass_phrase', 'secret');

        $this->seed();
    }

    public function test_one_time_checkout_uses_the_plans_real_price_regardless_of_client_supplied_amount(): void
    {
        $this->postJson('/payments/payfast/initiate', [
            'plan' => 'starter-onetime',
            // An attacker (or a stale client) sending these should have zero effect.
            'amount' => '0.01',
            'item_name' => 'Free stuff',
            'email_address' => 'customer@rainwaves.test',
            'm_payment_id' => 'RW-PLAN-AUTH-1',
        ])->assertOk();

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'RW-PLAN-AUTH-1',
            'item_name' => 'Starter Access',
            'amount_requested' => 499.00,
        ]);
    }

    public function test_subscription_checkout_uses_the_plans_real_price_and_frequency(): void
    {
        $this->postJson('/payments/payfast/subscriptions/initiate', [
            'plan' => 'starter-monthly',
            'billing_date' => '2026-09-01',
            'amount' => '1.00',
            'recurring_amount' => '1.00',
            'item_name' => 'Definitely not the real plan',
            'email_address' => 'customer@rainwaves.test',
            'm_payment_id' => 'SUB-PLAN-AUTH-1',
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'merchant_payment_id' => 'SUB-PLAN-AUTH-1',
            'item_name' => 'Starter Plan',
            'amount_requested' => 199.00,
            'recurring_amount' => 199.00,
            'frequency' => 3,
        ]);
    }

    public function test_unknown_plan_is_rejected_with_a_422(): void
    {
        $this->postJson('/payments/payfast/initiate', [
            'plan' => 'does-not-exist',
            'email_address' => 'customer@rainwaves.test',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_subscription_only_plan_is_rejected_on_the_one_time_endpoint(): void
    {
        $this->postJson('/payments/payfast/initiate', [
            'plan' => 'starter-monthly',
            'email_address' => 'customer@rainwaves.test',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan');
    }

    public function test_a_payment_only_plan_is_rejected_on_the_subscription_endpoint(): void
    {
        $this->postJson('/payments/payfast/subscriptions/initiate', [
            'plan' => 'starter-onetime',
            'billing_date' => '2026-09-01',
            'email_address' => 'customer@rainwaves.test',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('plan');
    }

    public function test_a_regular_customer_cannot_checkout_on_behalf_of_another_user(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $this->actingAs($customer)->postJson('/payments/payfast/initiate', [
            'plan' => 'starter-onetime',
            'user_id' => $owner->id,
            'email_address' => 'customer@rainwaves.test',
            'm_payment_id' => 'RW-NO-IMPERSONATION',
        ])->assertOk();

        $payment = Payment::query()->where('merchant_payment_id', 'RW-NO-IMPERSONATION')->firstOrFail();

        $this->assertSame($customer->id, $payment->user_id);
    }

    public function test_an_admin_with_payments_manage_can_checkout_on_behalf_of_another_user(): void
    {
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->assertTrue($owner->can('payments.manage'));

        $this->actingAs($owner)->postJson('/payments/payfast/initiate', [
            'plan' => 'starter-onetime',
            'user_id' => $customer->id,
            // The admin's browser doesn't represent the customer — these should be
            // ignored in favour of the target user's real profile.
            'name_first' => 'Wrong',
            'name_last' => 'Person',
            'email_address' => 'not-the-real-customer@example.com',
            'm_payment_id' => 'RW-ADMIN-ON-BEHALF',
        ])->assertOk();

        $payment = Payment::query()->where('merchant_payment_id', 'RW-ADMIN-ON-BEHALF')->firstOrFail();

        $this->assertSame($customer->id, $payment->user_id);
        $this->assertSame($customer->email, $payment->customer_email);
        $this->assertSame('Starter', $payment->customer_first_name);
        $this->assertSame('Customer', $payment->customer_last_name);
    }

    public function test_a_staff_user_without_payments_manage_cannot_checkout_on_behalf_of_another_user(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();

        $this->assertFalse($staff->can('payments.manage'));

        $this->actingAs($staff)->postJson('/payments/payfast/initiate', [
            'plan' => 'starter-onetime',
            'user_id' => $customer->id,
            'email_address' => $staff->email,
            'm_payment_id' => 'RW-STAFF-NO-MANAGE',
        ])->assertOk();

        $payment = Payment::query()->where('merchant_payment_id', 'RW-STAFF-NO-MANAGE')->firstOrFail();

        $this->assertSame($staff->id, $payment->user_id);
    }
}
