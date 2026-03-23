# Paystack Payment

`rainwaves/paystack-payment` is a Laravel Paystack payment package for Rainwaves applications.

## Scope

This package is intended to support host applications that need:
- payment customer creation
- provider plan creation
- checkout initialization
- transaction verification
- webhook verification
- refunds

The package does not contain application-specific subscription activation logic. That remains the responsibility of the host app.

## Current Driver

- `paystack`

## Configuration

The package publishes and reads the `paystack` config file.

Example keys:

```php
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
```

## Host App Billing Rule

In the current SYNC Discovery integration:
- plan catalog prices are maintained in `USD`
- Paystack charges are created in `ZAR`
- transaction amounts sent to Paystack must be in subunits

Example:
- `ZAR 125.50` must be sent as `12550`

## Main Interfaces

- `rainwaves\PaystackPayment\Contracts\PaymentGatewayInterface`

Key operations exposed by the Paystack driver:
- create customer
- create plan
- initialize checkout
- verify transaction
- verify webhook
- create refund

## Notes

- Webhook signature verification is handled in the driver using the configured Paystack webhook secret.
- The package returns structured DTOs so host applications can map provider responses into their own persistence and domain logic.
- Refund orchestration, local subscription state transitions, and UI flows belong in the host application layer.
