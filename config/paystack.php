<?php

return [
    'default' => env('PAYSTACK_PAYMENT_DRIVER', 'paystack'),
    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
        'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET', env('PAYSTACK_SECRET_KEY', '')),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
        'cancel_url' => env('PAYSTACK_CANCEL_URL'),
        'currency' => env('PAYSTACK_CURRENCY', 'ZAR'),
        'timeout' => (int) env('PAYSTACK_TIMEOUT', 15),
        'retry' => (int) env('PAYSTACK_RETRY', 2),
    ],
];
