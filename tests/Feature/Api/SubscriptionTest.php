<?php

namespace Tests\Feature\Api;

use App\Contracts\PayFastCheckoutServiceInterface;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Real user-facing subscription self-service: list own subscriptions, cancel own.
 * The actual PayFastClient HTTP call is mocked out via the service interface — that
 * transport is the vendor package's own tested concern; what matters here is the
 * ownership/state authorization this controller is responsible for.
 */
class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function makeSubscription(User $user, string $merchantPaymentId, SubscriptionStatus $status, string|false|null $token = null): Subscription
    {
        return Subscription::query()->create([
            'user_id' => $user->id,
            'merchant_payment_id' => $merchantPaymentId,
            'provider' => 'payfast',
            'token' => $token === false ? null : ($token ?? "token-{$merchantPaymentId}"),
            'item_name' => 'Growth plan',
            'amount_requested' => 99.00,
            'recurring_amount' => 99.00,
            'billing_date' => now()->addMonth(),
            'frequency' => 3,
            'cycles' => 0,
            'status' => $status,
            'initiated_at' => now(),
        ]);
    }

    public function test_index_lists_only_the_authenticated_users_own_subscriptions(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $this->makeSubscription($customer, 'SUB-MINE', SubscriptionStatus::Active);
        $this->makeSubscription($owner, 'SUB-NOT-MINE', SubscriptionStatus::Active);

        $this->actingAs($customer)
            ->getJson('/api/v1/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.merchant_payment_id', 'SUB-MINE');
    }

    public function test_cannot_cancel_another_users_subscription(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $owner = User::where('email', 'owner@rainwaves.test')->firstOrFail();

        $subscription = $this->makeSubscription($owner, 'SUB-OWNER', SubscriptionStatus::Active);

        $this->actingAs($customer)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertStatus(403);

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
    }

    public function test_cannot_cancel_a_subscription_with_no_token(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $subscription = $this->makeSubscription($customer, 'SUB-NO-TOKEN', SubscriptionStatus::Active, token: false);

        $this->actingAs($customer)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertStatus(422);
    }

    public function test_cannot_cancel_an_already_cancelled_subscription(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $subscription = $this->makeSubscription($customer, 'SUB-DONE', SubscriptionStatus::Cancelled);

        $this->actingAs($customer)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertStatus(422);
    }

    public function test_can_cancel_own_active_subscription(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $subscription = $this->makeSubscription($customer, 'SUB-ACTIVE', SubscriptionStatus::Active);

        $this->mock(PayFastCheckoutServiceInterface::class, function ($mock) use ($subscription) {
            $mock->shouldReceive('cancelSubscription')
                ->once()
                ->withArgs(fn (Subscription $arg) => $arg->is($subscription))
                ->andReturnUsing(function (Subscription $sub) {
                    $sub->forceFill(['status' => SubscriptionStatus::Cancelled, 'cancelled_at' => now()])->save();

                    return ['successful' => true, 'provider_message' => null];
                });
        });

        $this->actingAs($customer)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_surfaces_provider_failure_message_when_cancellation_is_declined(): void
    {
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $subscription = $this->makeSubscription($customer, 'SUB-DECLINE', SubscriptionStatus::Active);

        $this->mock(PayFastCheckoutServiceInterface::class, function ($mock) {
            $mock->shouldReceive('cancelSubscription')
                ->once()
                ->andReturn(['successful' => false, 'provider_message' => 'Token not found']);
        });

        $this->actingAs($customer)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Token not found');

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
    }

    public function test_a_payfast_transport_failure_is_a_handled_422_not_an_uncaught_500(): void
    {
        // Reproduces a real failure found via live testing: cancelling a subscription
        // whose token was never actually issued by PayFast (e.g. fabricated by local
        // dev tooling) makes PayFast's native API return an HTML error page instead
        // of JSON, which the vendor client turns into a thrown exception.
        $customer = User::where('email', 'customer@rainwaves.test')->firstOrFail();
        $subscription = $this->makeSubscription($customer, 'SUB-TRANSPORT-FAIL', SubscriptionStatus::Active);

        $this->mock(PayFastCheckoutServiceInterface::class, function ($mock) {
            $mock->shouldReceive('cancelSubscription')
                ->once()
                ->andThrow(new RuntimeException('PayFast API returned unsupported content type: text/html; charset=utf-8'));
        });

        $this->actingAs($customer)
            ->postJson("/api/v1/subscriptions/{$subscription->id}/cancel")
            ->assertStatus(422)
            ->assertJsonPath('message', 'PayFast could not be reached to cancel this subscription. Please try again shortly.');

        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
    }
}
