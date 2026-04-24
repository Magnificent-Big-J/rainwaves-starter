<?php

namespace App\Http\Controllers;

use App\Contracts\PayFastCheckoutServiceInterface;
use App\Http\Requests\Payments\InitiateOneTimePaymentRequest;
use App\Http\Requests\Payments\InitiateSubscriptionPaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    public function handleReturn(Request $request): Response
    {
        $this->checkout->markReturn(
            $request->query('m_payment_id'),
            $request->query('token')
        );

        return response('Payment completed');
    }

    public function handleCancel(Request $request): Response
    {
        $this->checkout->markCancelled(
            $request->query('m_payment_id'),
            $request->query('token')
        );

        return response('Payment canceled');
    }
}
