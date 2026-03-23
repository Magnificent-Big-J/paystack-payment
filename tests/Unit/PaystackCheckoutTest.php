<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use rainwaves\PaystackPayment\DTO\CheckoutInitializationData;
use rainwaves\PaystackPayment\Exceptions\InvalidPaymentRequestException;
use rainwaves\PaystackPayment\Exceptions\PaymentGatewayException;
use rainwaves\PaystackPayment\Tests\TestCase;

class PaystackCheckoutTest extends TestCase
{
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

        $result = $this->gateway(http: $http)->initializeCheckout(new CheckoutInitializationData(
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

        $this->gateway(http: $http)->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-USD-1',
            email: 'customer@example.com',
            amountInMinor: 2500,
            currency: 'usd',
        ));

        $request = $http->recorded()->map(fn (array $pair) => $pair[0])->all()[0];
        $this->assertSame('USD', $request->data()['currency']);
    }

    public function test_it_throws_when_no_currency_is_available(): void
    {
        $this->expectException(PaymentGatewayException::class);
        $this->expectExceptionMessage(
            'Paystack currency is required. Set paystack.paystack.currency or pass a currency per request.'
        );

        $this->gateway(['currency' => ''])->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-NO-CURRENCY',
            email: 'customer@example.com',
            amountInMinor: 12550,
        ));
    }

    public function test_it_rejects_invalid_checkout_email(): void
    {
        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The checkout email field must contain a valid email address.');

        $this->gateway()->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-INVALID-EMAIL',
            email: 'not-an-email',
            amountInMinor: 12550,
        ));
    }

    public function test_it_rejects_non_positive_checkout_amount(): void
    {
        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The checkout amount field must be greater than zero.');

        $this->gateway()->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-ZERO-AMOUNT',
            email: 'customer@example.com',
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

        $this->gateway(http: $http)->initializeCheckout(new CheckoutInitializationData(
            reference: 'ORDER-1001',
            email: 'customer@example.com',
            amountInMinor: 12550,
        ));
    }
}
