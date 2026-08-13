<?php

namespace App\Http\Controllers;

use App\Contracts\PayFastCheckoutServiceInterface;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\Payments\InitiateOneTimePaymentRequest;
use App\Http\Requests\Payments\InitiateSubscriptionPaymentRequest;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use rainwaves\PayfastPayment\Client\PayFastClient;
use rainwaves\PayfastPayment\Request\AdhocChargeRequest;
use rainwaves\PayfastPayment\Request\CardUpdateLinkRequest;
use rainwaves\PayfastPayment\Request\PauseSubscriptionRequest;
use rainwaves\PayfastPayment\Request\UpdateSubscriptionRequest;
use rainwaves\PayfastPayment\Support\Money;
use rainwaves\PayfastPayment\Support\PayFastSignatureHelper;
use Throwable;

class PayFastController extends Controller
{
    public function __construct(
        private readonly PayFastCheckoutServiceInterface $checkout
    ) {}

    public function initiateOneTime(InitiateOneTimePaymentRequest $request): Response
    {
        $result = $this->checkout->initiateOneTimePayment(
            $request->validated(),
            $request->user()?->id
        );

        return response($result['html'], 200)->header('Content-Type', 'text/html');
    }

    public function records(): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        return response()->json([
            'payments' => Payment::query()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'merchant_payment_id' => $payment->merchant_payment_id,
                    'payfast_payment_id' => $payment->payfast_payment_id,
                    'item_name' => $payment->item_name,
                    'amount_requested' => $this->money($payment->amount_requested),
                    'amount_gross' => $this->money($payment->amount_gross),
                    'customer_email' => $payment->customer_email,
                    'status' => $payment->status?->value ?? (string) $payment->status,
                    'payment_status' => $payment->payment_status,
                    'initiated_at' => $payment->initiated_at?->toIso8601String(),
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                    'created_at' => $payment->created_at?->toIso8601String(),
                ]),
            'subscriptions' => Subscription::query()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (Subscription $subscription) => [
                    'id' => $subscription->id,
                    'merchant_payment_id' => $subscription->merchant_payment_id,
                    'token' => $subscription->token,
                    'item_name' => $subscription->item_name,
                    'amount_requested' => $this->money($subscription->amount_requested),
                    'recurring_amount' => $this->money($subscription->recurring_amount),
                    'billing_date' => $subscription->billing_date?->toDateString(),
                    'frequency' => $subscription->frequency,
                    'cycles' => $subscription->cycles,
                    'customer_email' => $subscription->customer_email,
                    'status' => $subscription->status?->value ?? (string) $subscription->status,
                    'payment_status' => $subscription->payment_status,
                    'initiated_at' => $subscription->initiated_at?->toIso8601String(),
                    'activated_at' => $subscription->activated_at?->toIso8601String(),
                    'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
                    'created_at' => $subscription->created_at?->toIso8601String(),
                ]),
            'events' => PaymentEvent::query()
                ->with(['payment:id,merchant_payment_id', 'subscription:id,merchant_payment_id'])
                ->latest('received_at')
                ->latest()
                ->limit(30)
                ->get()
                ->map(fn (PaymentEvent $event) => [
                    'id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_ref' => $event->event_ref,
                    'provider' => $event->provider,
                    'payment_id' => $event->payment_id,
                    'subscription_id' => $event->subscription_id,
                    'merchant_payment_id' => $event->payment?->merchant_payment_id ?? $event->subscription?->merchant_payment_id,
                    'payload' => $event->payload,
                    'received_at' => $event->received_at?->toIso8601String(),
                    'created_at' => $event->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function initiateSubscription(InitiateSubscriptionPaymentRequest $request): Response
    {
        $result = $this->checkout->initiateSubscriptionPayment(
            $request->validated(),
            $request->user()?->id
        );

        return response($result['html'], 200)->header('Content-Type', 'text/html');
    }

    public function itn(Request $request): Response
    {
        $result = $this->checkout->processItn($request->all(), $request->getContent());

        logger()->info('payfast.itn', $result);

        if (! ($result['accepted'] ?? false)) {
            $status = $result['reason'] === 'invalid_signature' ? 400 : 422;

            return response($result['reason'], $status);
        }

        return response($result['duplicate'] ? 'duplicate' : 'ok', 200);
    }

    public function simulateItn(Request $request): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:payment,subscription'],
            'id' => ['required', 'integer'],
            'payment_status' => ['required', 'string', 'in:COMPLETE,PENDING,FAILED,CANCELLED'],
        ]);

        $payload = $validated['type'] === 'subscription'
            ? $this->subscriptionItnPayload(Subscription::query()->findOrFail($validated['id']), $validated['payment_status'])
            : $this->paymentItnPayload(Payment::query()->findOrFail($validated['id']), $validated['payment_status']);

        $signatureFields = [...$payload, 'passphrase' => (string) config('payfast.pass_phrase', '')];
        ksort($signatureFields);
        $payload['signature'] = md5(PayFastSignatureHelper::buildQuery($signatureFields));

        $result = $this->checkout->processItn($payload, PayFastSignatureHelper::buildQuery($payload));

        return response()->json([
            'result' => $result,
            'payload' => $payload,
        ]);
    }

    public function subscriptionAction(Request $request): JsonResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:fetch,pause,unpause,cancel,card_update_link,update,adhoc'],
            'pause_cycles' => ['nullable', 'integer', 'min:1', 'max:24'],
            'update_amount' => ['nullable', 'numeric', 'min:0.01'],
            'update_frequency' => ['nullable', 'integer', 'in:1,2,3,4,5,6'],
            'update_cycles' => ['nullable', 'integer', 'min:0'],
            'update_run_date' => ['nullable', 'date_format:Y-m-d'],
            'adhoc_amount' => ['nullable', 'numeric', 'min:0.01'],
            'adhoc_item_name' => ['nullable', 'string', 'max:100'],
            'adhoc_item_description' => ['nullable', 'string', 'max:255'],
        ]);

        $subscription = Subscription::query()->findOrFail($validated['id']);

        if (! $subscription->token) {
            return response()->json([
                'message' => 'A PayFast subscription token is required before native subscription actions can run.',
            ], 422);
        }

        try {
            $result = $this->runSubscriptionAction($subscription, $validated);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $payload = [
            'action' => $validated['action'],
            'successful' => $result->successful(),
            'status_code' => $result->statusCode(),
            'provider_code' => $result->providerCode(),
            'provider_message' => $result->providerMessage(),
            'data' => $result->data(),
        ];

        if (method_exists($result, 'url')) {
            $payload['url'] = $result->url();
        }

        PaymentEvent::query()->create([
            'subscription_id' => $subscription->id,
            'provider' => 'payfast',
            'event_type' => 'subscription_action_'.$validated['action'],
            'event_ref' => $subscription->token.':'.$validated['action'].':'.Str::uuid(),
            'payload' => $payload,
            'received_at' => now(),
        ]);

        if ($validated['action'] === 'cancel' && $result->successful()) {
            $subscription->forceFill([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();
        }

        return response()->json([
            'result' => $payload,
        ], $result->successful() ? 200 : 422);
    }

    /**
     * Purely cosmetic redirect for the buyer's browser. PayFast's server-to-server ITN
     * call (self::itn) is the only path that is ever allowed to change payment/subscription
     * state — a buyer can edit this URL freely (wrong id, wrong outcome, replay) and it must
     * not be able to mark anything paid, returned, or cancelled.
     */
    public function handleReturn(Request $request): RedirectResponse
    {
        return redirect($this->resultRedirectTarget().'?'.http_build_query([
            'payfast_result' => 'return',
            'm_payment_id' => $request->query('m_payment_id'),
            'token' => $request->query('token'),
        ]));
    }

    public function handleCancel(Request $request): RedirectResponse
    {
        return redirect($this->resultRedirectTarget().'?'.http_build_query([
            'payfast_result' => 'cancel',
            'm_payment_id' => $request->query('m_payment_id'),
            'token' => $request->query('token'),
        ]));
    }

    private function resultRedirectTarget(): string
    {
        return app()->environment(['local', 'testing']) ? '/payfast-browser-test' : '/dashboard';
    }

    private function paymentItnPayload(Payment $payment, string $status): array
    {
        $gross = $this->money($payment->amount_requested);
        $fee = $status === 'COMPLETE' ? '2.30' : '0.00';

        return [
            'merchant_id' => (string) config('payfast.merchant_id'),
            'm_payment_id' => $payment->merchant_payment_id,
            'pf_payment_id' => 'PF-'.Str::upper(Str::random(10)),
            'payment_status' => $status,
            'item_name' => $payment->item_name,
            'item_description' => $payment->item_description ?? '',
            'amount_gross' => $gross,
            'amount_fee' => $fee,
            'amount_net' => $this->money(((float) $gross) - ((float) $fee)),
            'name_first' => $payment->customer_first_name ?? 'Rainwaves',
            'name_last' => $payment->customer_last_name ?? 'Customer',
            'email_address' => $payment->customer_email ?? 'customer@rainwaves.test',
        ];
    }

    private function subscriptionItnPayload(Subscription $subscription, string $status): array
    {
        $gross = $this->money($subscription->amount_requested);
        $fee = $status === 'COMPLETE' ? '2.30' : '0.00';

        return [
            'merchant_id' => (string) config('payfast.merchant_id'),
            'm_payment_id' => $subscription->merchant_payment_id,
            'pf_payment_id' => 'PF-'.Str::upper(Str::random(10)),
            'token' => $subscription->token ?: 'TOKEN-'.Str::upper(Str::random(12)),
            'payment_status' => $status,
            'item_name' => $subscription->item_name,
            'amount_gross' => $gross,
            'amount_fee' => $fee,
            'amount_net' => $this->money(((float) $gross) - ((float) $fee)),
            'billing_date' => $subscription->billing_date?->toDateString() ?? now()->addMonth()->toDateString(),
            'name_first' => $subscription->customer_first_name ?? 'Rainwaves',
            'name_last' => $subscription->customer_last_name ?? 'Customer',
            'email_address' => $subscription->customer_email ?? 'customer@rainwaves.test',
        ];
    }

    private function runSubscriptionAction(Subscription $subscription, array $validated): mixed
    {
        $client = PayFastClient::make($this->payFastConfig())->subscriptions();
        $token = (string) $subscription->token;

        return match ($validated['action']) {
            'fetch' => $client->fetch($token),
            'pause' => $client->pause($token, new PauseSubscriptionRequest((int) ($validated['pause_cycles'] ?? 1))),
            'unpause' => $client->unpause($token),
            'cancel' => $client->cancel($token),
            'card_update_link' => $client->cardUpdateLink($token, new CardUpdateLinkRequest(config('payfast.return_url'))),
            'update' => $client->update($token, UpdateSubscriptionRequest::make(
                cycles: isset($validated['update_cycles']) ? (int) $validated['update_cycles'] : null,
                frequency: isset($validated['update_frequency']) ? (int) $validated['update_frequency'] : null,
                runDate: $validated['update_run_date'] ?? null,
                amount: isset($validated['update_amount']) ? Money::zar($this->money($validated['update_amount'])) : null,
            )),
            'adhoc' => $client->adhoc($token, new AdhocChargeRequest(
                amount: Money::zar($this->money($validated['adhoc_amount'] ?? $subscription->recurring_amount)),
                itemName: $validated['adhoc_item_name'] ?? ($subscription->item_name.' ad hoc'),
                mPaymentId: 'ADHOC-'.$subscription->merchant_payment_id.'-'.Str::upper(Str::random(6)),
                itemDescription: $validated['adhoc_item_description'] ?? null,
                sendItn: true,
            )),
        };
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

    private function money(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
