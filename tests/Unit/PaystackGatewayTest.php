<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use PHPUnit\Framework\TestCase;
use rainwaves\PaystackPayment\DTO\CheckoutInitializationData;
use rainwaves\PaystackPayment\DTO\PlanData;
use rainwaves\PaystackPayment\DTO\WebhookPayload;
use rainwaves\PaystackPayment\Drivers\Paystack\PaystackGateway;
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
    }
}
