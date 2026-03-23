<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use PHPUnit\Framework\TestCase;
use rainwaves\PaystackPayment\DTO\CheckoutInitializationData;
use rainwaves\PaystackPayment\DTO\CustomerData;
use rainwaves\PaystackPayment\DTO\PlanData;
use rainwaves\PaystackPayment\DTO\RefundData;
use rainwaves\PaystackPayment\DTO\WebhookPayload;
use rainwaves\PaystackPayment\Drivers\Paystack\PaystackGateway;
use rainwaves\PaystackPayment\Exceptions\InvalidPaymentRequestException;
use rainwaves\PaystackPayment\Exceptions\PaymentGatewayException;

class PaystackGatewayTest extends TestCase
{
    private function gateway(array $config = []): PaystackGateway
    {
        return new PaystackGateway(
            new HttpFactory(),
            array_replace([
                'secret_key' => 'sk_test_package',
                'public_key' => 'pk_test_package',
                'webhook_secret' => 'whsec_test_package',
                'base_url' => 'https://api.paystack.co',
                'currency' => 'ZAR',
                'timeout' => 15,
                'retry' => 0,
            ], $config),
        );
    }

    public function test_it_uses_the_configured_currency_for_single_payment_checkout(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/transaction/initialize' => HttpFactory::response([
                'data' => [
                    'reference' => 'ORDER-1001',
                    'authorization_url' => 'https://checkout.paystack.com/test',
                    'access_code' => 'ACCESS_CODE',
                ],
            ]),
        ]);

        $result = new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-1001',
            email: 'customer@example.com',
            amountInMinor: 12550,
            metadata: [
                'type' => 'single_payment',
            ],
        ));

        $request = $http->recorded()->map(fn (array $pair) => $pair[0])->all()[0];
        $data = $request->data();

        $this->assertSame('https://api.paystack.co/transaction/initialize', $request->url());
        $this->assertSame('ZAR', $data['currency']);
        $this->assertSame(12550, $data['amount']);
        $this->assertSame('ORDER-1001', $data['reference']);
        $this->assertArrayNotHasKey('plan', $data);

        $this->assertSame('ORDER-1001', $result->reference);
        $this->assertSame('https://checkout.paystack.com/test', $result->checkoutUrl);
    }

    public function test_it_creates_a_customer(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/customer' => HttpFactory::response([
                'data' => [
                    'customer_code' => 'CUS_123',
                    'email' => 'customer@example.com',
                ],
            ]),
        ]);

        $result = new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->createCustomer(new CustomerData(
            email: 'customer@example.com',
            firstName: 'Joel',
            lastName: 'Mnisi',
            phone: '27820000000',
            metadata: ['user_id' => 12],
        ));

        $request = $http->recorded()->map(fn (array $pair) => $pair[0])->all()[0];
        $data = $request->data();

        $this->assertSame('https://api.paystack.co/customer', $request->url());
        $this->assertSame('customer@example.com', $data['email']);
        $this->assertSame('Joel', $data['first_name']);
        $this->assertSame('Mnisi', $data['last_name']);
        $this->assertSame('27820000000', $data['phone']);
        $this->assertSame('CUS_123', $result->code);
        $this->assertSame('customer@example.com', $result->email);
    }

    public function test_it_allows_overriding_currency_for_checkout(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/transaction/initialize' => HttpFactory::response([
                'data' => [
                    'reference' => 'ORDER-USD-1',
                    'authorization_url' => 'https://checkout.paystack.com/usd',
                ],
            ]),
        ]);

        new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-USD-1',
            email: 'customer@example.com',
            amountInMinor: 2500,
            currency: 'usd',
        ));

        $request = $http->recorded()->map(fn (array $pair) => $pair[0])->all()[0];
        $this->assertSame('USD', $request->data()['currency']);
    }

    public function test_it_uses_the_configured_currency_for_plan_creation(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/plan' => HttpFactory::response([
                'data' => [
                    'plan_code' => 'PLN_123',
                    'name' => 'Gold Plan',
                    'interval' => 'monthly',
                    'amount' => 9900,
                    'currency' => 'ZAR',
                ],
            ]),
        ]);

        $plan = new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->createPlan(new PlanData(
            name: 'Gold Plan',
            amountInMinor: 9900,
            interval: 'monthly',
        ));

        $request = $http->recorded()->map(fn (array $pair) => $pair[0])->all()[0];
        $this->assertSame('https://api.paystack.co/plan', $request->url());
        $this->assertSame('ZAR', $request->data()['currency']);

        $this->assertSame('PLN_123', $plan->code);
        $this->assertSame('ZAR', $plan->currency);
    }

    public function test_it_creates_a_refund(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/refund' => HttpFactory::response([
                'data' => [
                    'status' => 'processed',
                    'amount' => 9900,
                    'currency' => 'ZAR',
                    'transaction' => [
                        'reference' => 'ORDER-REFUND-1',
                    ],
                ],
            ]),
        ]);

        $result = new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->createRefund(new RefundData(
            transactionReference: 'ORDER-REFUND-1',
            amountInMinor: 9900,
            reason: 'Customer requested cancellation',
        ));

        $request = $http->recorded()->map(fn (array $pair) => $pair[0])->all()[0];
        $data = $request->data();

        $this->assertSame('https://api.paystack.co/refund', $request->url());
        $this->assertSame('ORDER-REFUND-1', $data['transaction']);
        $this->assertSame(9900, $data['amount']);
        $this->assertSame('Customer requested cancellation', $data['merchant_note']);
        $this->assertSame('ORDER-REFUND-1', $result->reference);
        $this->assertSame('processed', $result->status);
        $this->assertSame(9900, $result->amountInMinor);
    }

    public function test_it_throws_when_no_currency_is_available(): void
    {
        $gateway = $this->gateway(['currency' => '']);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage(
            'Paystack currency is required. Set paystack.paystack.currency or pass a currency per request.'
        );

        $gateway->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-NO-CURRENCY',
            email: 'customer@example.com',
            amountInMinor: 12550,
        ));
    }

    public function test_it_rejects_blank_plan_name(): void
    {
        $gateway = $this->gateway();

        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The plan name field is required.');

        $gateway->createPlan(new PlanData(
            name: ' ',
            amountInMinor: 9900,
            interval: 'monthly',
        ));
    }

    public function test_it_rejects_invalid_customer_email(): void
    {
        $gateway = $this->gateway();

        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The customer email field must contain a valid email address.');

        $gateway->createCustomer(new CustomerData(
            email: 'wrong-email',
        ));
    }

    public function test_it_rejects_blank_refund_reference(): void
    {
        $gateway = $this->gateway();

        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The refund transaction reference field is required.');

        $gateway->createRefund(new RefundData(
            transactionReference: ' ',
        ));
    }

    public function test_it_rejects_non_positive_refund_amount(): void
    {
        $gateway = $this->gateway();

        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The refund amount field must be greater than zero.');

        $gateway->createRefund(new RefundData(
            transactionReference: 'ORDER-REFUND-INVALID',
            amountInMinor: 0,
        ));
    }

    public function test_it_throws_when_checkout_returns_no_authorization_url(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/transaction/initialize' => HttpFactory::response([
                'data' => [
                    'reference' => 'ORDER-1001',
                ],
            ]),
        ]);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Paystack checkout initialization returned no authorization URL.');

        new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-1001',
            email: 'customer@example.com',
            amountInMinor: 12550,
        ));
    }

    public function test_it_marks_invalid_webhook_signatures_as_invalid(): void
    {
        $verification = $this->gateway()->verifyWebhook(new WebhookPayload(
            rawBody: '{"event":"charge.success","data":{"reference":"ORDER-1001"}}',
            headers: [
                'x-paystack-signature' => 'invalid-signature',
            ],
            payload: [
                'event' => 'charge.success',
                'data' => [
                    'reference' => 'ORDER-1001',
                ],
            ],
        ));

        $this->assertFalse($verification->isValid);
        $this->assertTrue($verification->isChargeSuccess());
    }

    public function test_it_throws_when_transaction_verification_fails(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/transaction/verify/ORDER-404' => HttpFactory::response([
                'message' => 'Transaction not found',
            ], 404),
        ]);

        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage('Paystack transaction verification failed: {"message":"Transaction not found"}');

        new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->verifyTransaction('ORDER-404');
    }

    public function test_it_validates_webhook_signature(): void
    {
        $rawBody = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'ORDER-1001',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha512', $rawBody, 'whsec_test_package');

        $verification = $this->gateway()->verifyWebhook(new WebhookPayload(
            rawBody: $rawBody,
            headers: [
                'x-paystack-signature' => $signature,
            ],
            payload: json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR),
        ));

        $this->assertTrue($verification->isValid);
        $this->assertSame('charge.success', $verification->eventType);
        $this->assertSame('ORDER-1001', $verification->reference);
        $this->assertTrue($verification->isChargeSuccess());
        $this->assertFalse($verification->isSubscriptionCreate());
    }

    public function test_it_maps_richer_transaction_verification_fields(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/transaction/verify/ORDER-1001' => HttpFactory::response([
                'data' => [
                    'reference' => 'ORDER-1001',
                    'status' => 'success',
                    'amount' => 12550,
                    'currency' => 'ZAR',
                    'paid_at' => '2026-03-23T09:30:00.000Z',
                    'fees' => 250,
                    'channel' => 'card',
                    'gateway_response' => 'Successful',
                    'customer' => [
                        'customer_code' => 'CUS_123',
                        'email' => 'customer@example.com',
                        'first_name' => 'Joel',
                        'last_name' => 'Mnisi',
                    ],
                    'plan' => [
                        'plan_code' => 'PLN_123',
                    ],
                    'subscription' => [
                        'subscription_code' => 'SUB_123',
                    ],
                    'authorization' => [
                        'authorization_code' => 'AUTH_123',
                        'reusable' => true,
                    ],
                ],
            ]),
        ]);

        $result = new PaystackGateway($http, [
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ])->verifyTransaction('ORDER-1001');

        $this->assertSame('customer@example.com', $result->customerEmail);
        $this->assertSame('Joel Mnisi', $result->customerName);
        $this->assertSame('AUTH_123', $result->authorizationCode);
        $this->assertTrue($result->authorizationReusable);
        $this->assertSame('2026-03-23T09:30:00.000Z', $result->paidAt);
        $this->assertSame(250, $result->feesInMinor);
        $this->assertSame('card', $result->channel);
        $this->assertSame('Successful', $result->gatewayResponse);
    }

    public function test_it_rejects_invalid_checkout_email(): void
    {
        $gateway = $this->gateway();

        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The checkout email field must contain a valid email address.');

        $gateway->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-INVALID-EMAIL',
            email: 'not-an-email',
            amountInMinor: 12550,
        ));
    }

    public function test_it_rejects_non_positive_checkout_amount(): void
    {
        $gateway = $this->gateway();

        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The checkout amount field must be greater than zero.');

        $gateway->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-ZERO-AMOUNT',
            email: 'customer@example.com',
            amountInMinor: 0,
        ));
    }
}
