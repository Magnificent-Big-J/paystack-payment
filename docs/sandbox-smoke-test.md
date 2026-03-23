# Paystack Sandbox Smoke Test

Use this checklist before tagging a production release.

## Package Setup

1. Install the package in a Laravel host app with `composer require rainwaves/paystack-payment`.
2. Publish the package config.
3. Set sandbox values for:
   - `PAYSTACK_SECRET_KEY`
   - `PAYSTACK_PUBLIC_KEY`
   - `PAYSTACK_WEBHOOK_SECRET`
   - `PAYSTACK_BASE_URL=https://api.paystack.co`
   - `PAYSTACK_CALLBACK_URL`
   - `PAYSTACK_CANCEL_URL`
   - `PAYSTACK_CURRENCY`

## Positive Flow

1. Resolve `rainwaves\PaystackPayment\Contracts\PaymentGatewayInterface` from the container.
2. Create a customer and confirm a `customer_code` is returned.
3. Initialize a single payment and confirm an `authorization_url` is returned.
4. Complete a sandbox checkout and confirm `verifyTransaction()` returns:
   - `status=success`
   - the expected `reference`
   - the expected currency
   - `channel`, `feesInMinor`, and `gatewayResponse` when Paystack provides them
5. Create a recurring plan and initialize a subscription checkout.
6. Receive a real webhook on a public endpoint and confirm:
   - signature verification passes
   - the correct helper method matches the event
   - the host app updates local state correctly
7. Create a refund and confirm the mapped refund status and reference.

## Negative Flow

1. Send an invalid webhook signature and confirm the host app rejects it.
2. Attempt checkout with an invalid email and confirm `InvalidPaymentRequestException`.
3. Attempt checkout with a zero amount and confirm `InvalidPaymentRequestException`.
4. Force a failed Paystack API response and confirm `PaymentGatewayException`.

## Release Gate

Do not tag a production release until all of these are true:

- `composer lint` passes
- `composer test` passes
- sandbox single-payment flow passes end to end
- sandbox subscription flow passes end to end
- webhook round-trip passes from a public endpoint
- refund flow has been validated in sandbox
