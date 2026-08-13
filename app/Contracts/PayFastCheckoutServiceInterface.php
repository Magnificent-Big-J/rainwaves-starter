<?php

namespace App\Contracts;

interface PayFastCheckoutServiceInterface
{
    public function initiateOneTimePayment(array $data, ?int $userId = null): array;

    public function initiateSubscriptionPayment(array $data, ?int $userId = null): array;

    public function processItn(array $payload, string $rawBody = ''): array;
}
