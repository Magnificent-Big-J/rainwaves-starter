<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PayFastCheckoutServiceInterface;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Http\Responses\Envelope;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Production-safe subscription self-service: list your own subscriptions, cancel
 * your own. Distinct from PayFastController::subscriptionAction (routes/payfast-local.php)
 * which has no ownership check at all — that's fine there because it's an
 * admin-only, local/testing-only inspection tool, but it must never be reachable
 * by an ordinary authenticated user in production. Everything here is scoped to
 * the caller's own user_id.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PayFastCheckoutServiceInterface $checkout
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('per_page', 10), 100));

        $subscriptions = Subscription::query()
            ->where('user_id', $request->user()->getKey())
            ->latest()
            ->paginate($perPage);

        return Envelope::success(SubscriptionResource::collection($subscriptions));
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        if ($subscription->user_id !== $request->user()->getKey()) {
            abort(403);
        }

        if (! $subscription->token) {
            return Envelope::error('This subscription has no PayFast token yet — it cannot be cancelled through PayFast.', [], 422);
        }

        // Not $subscription->status->isTerminal() — that reports Active as terminal too
        // (see CLAUDE.md's Sanctum/guard gotcha note for the same landmine elsewhere).
        // Cancelled/Failed are the only statuses that genuinely can't be cancelled again.
        if (in_array($subscription->status, [SubscriptionStatus::Cancelled, SubscriptionStatus::Failed], true)) {
            return Envelope::error('This subscription is already '.$subscription->status->label().'.', [], 422);
        }

        // PayFast's native subscription API is a real network call (unlike checkout
        // initiation, which just builds a form) — it can throw on transport failure,
        // a non-JSON response, timeouts, etc. That must surface as a normal "the
        // action failed" 422, not bubble up into an uncaught 500.
        try {
            $result = $this->checkout->cancelSubscription($subscription);
        } catch (Throwable $exception) {
            return Envelope::error('PayFast could not be reached to cancel this subscription. Please try again shortly.', [], 422);
        }

        if (! $result['successful']) {
            return Envelope::error($result['provider_message'] ?? 'PayFast declined the cancellation request.', [], 422);
        }

        return Envelope::success(new SubscriptionResource($subscription->fresh()), 'Subscription cancelled.');
    }
}
