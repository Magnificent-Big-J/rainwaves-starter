<?php

use rainwaves\PayfastPayment\Model\Frequency;

/*
|--------------------------------------------------------------------------
| Billing plans catalog
|--------------------------------------------------------------------------
|
| Server-side pricing authority for PayFast checkout. Checkout requests
| reference a plan key here — the client never sends amount/item_name
| directly, and PayFastCheckoutService resolves the real price/description
| from this file, not from request input. See docs/crud-contract.md's
| sibling concern: this is the same "server is the source of truth" idea
| applied to money instead of a CRUD resource.
|
| These are placeholder starter plans — a real project replaces this array
| with its actual product catalog. Keys are stable identifiers referenced
| by the frontend and by `plan` in checkout requests; renaming a key is a
| breaking change for anything that references it.
|
*/

return [

    'starter-monthly' => [
        'mode' => 'subscription',
        'item_name' => 'Starter Plan',
        'item_description' => 'Monthly subscription to the Starter tier.',
        'amount' => 199.00,
        'frequency' => Frequency::MONTHLY,
    ],

    'starter-annual' => [
        'mode' => 'subscription',
        'item_name' => 'Starter Plan (Annual)',
        'item_description' => 'Annual subscription to the Starter tier — two months free versus monthly billing.',
        'amount' => 1990.00,
        'frequency' => Frequency::ANNUAL,
    ],

    'starter-onetime' => [
        'mode' => 'payment',
        'item_name' => 'Starter Access',
        'item_description' => 'One-time purchase of Starter tier access.',
        'amount' => 499.00,
    ],

];
