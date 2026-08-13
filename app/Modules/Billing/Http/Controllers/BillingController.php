<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\Billing\Http\Resources\PaymentEventResource;
use App\Modules\Billing\Http\Resources\PaymentResource;
use App\Modules\Billing\Http\Resources\SubscriptionResource;
use App\Modules\Billing\Models\Payment;
use App\Modules\Billing\Models\PaymentEvent;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The authenticated user's own billing summary: latest payment, latest
 * subscription, and recent payment events. Powers the dashboard/customer-home
 * billing widgets and the Billing page — all real data, scoped to the caller.
 */
class BillingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()->getKey();

        $latestPayment = Payment::query()
            ->where('user_id', $userId)
            ->latest()
            ->first();

        $latestSubscription = Subscription::query()
            ->where('user_id', $userId)
            ->orderByRaw("status = 'active' desc")
            ->latest()
            ->first();

        $paymentIds = Payment::query()->where('user_id', $userId)->pluck('id');
        $subscriptionIds = Subscription::query()->where('user_id', $userId)->pluck('id');

        $recentEvents = PaymentEvent::query()
            ->whereIn('payment_id', $paymentIds)
            ->orWhereIn('subscription_id', $subscriptionIds)
            ->latest('received_at')
            ->limit(5)
            ->get();

        return Envelope::success([
            'latest_payment' => $latestPayment ? new PaymentResource($latestPayment) : null,
            'latest_subscription' => $latestSubscription ? new SubscriptionResource($latestSubscription) : null,
            'recent_events' => PaymentEventResource::collection($recentEvents),
        ]);
    }
}
