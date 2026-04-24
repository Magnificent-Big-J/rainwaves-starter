<?php

$appUrl = rtrim((string) env('APP_URL', 'http://localhost'), '/');

return [
    'merchant_id'  => env('PAYFAST_MERCHANT_ID', env('MERCHANT_ID', '10000100')),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY', env('MERCHANT_KEY', '46f0cd694581a')),
    'env'          => env('PAYFAST_ENV', env('ENVIRONMENT', 'local')),
    'return_url'   => env('PAYFAST_RETURN_URL', env('RETURN_URL', "{$appUrl}/payments/payfast/return")),
    'cancel_url'   => env('PAYFAST_CANCEL_URL', env('CANCEL_URL', "{$appUrl}/payments/payfast/cancel")),
    'notify_url'   => env('PAYFAST_NOTIFY_URL', env('NOTIFY_URL', "{$appUrl}/payments/payfast/notify")),
    'pass_phrase'  => env('PAYFAST_PASS_PHRASE', env('PASS_PHRASE', '')),
];
