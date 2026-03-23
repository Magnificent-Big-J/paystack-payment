<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use rainwaves\PaystackPayment\Exceptions\PaymentGatewayException;
use rainwaves\PaystackPayment\Tests\TestCase;

class PaystackTransactionVerificationTest extends TestCase
{
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

        $result = $this->gateway(http: $http)->verifyTransaction('ORDER-1001');

        $this->assertSame('customer@example.com', $result->customerEmail);
        $this->assertSame('Joel Mnisi', $result->customerName);
        $this->assertSame('AUTH_123', $result->authorizationCode);
        $this->assertTrue($result->authorizationReusable);
        $this->assertSame('2026-03-23T09:30:00.000Z', $result->paidAt);
        $this->assertSame(250, $result->feesInMinor);
        $this->assertSame('card', $result->channel);
        $this->assertSame('Successful', $result->gatewayResponse);
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

        $this->gateway(http: $http)->verifyTransaction('ORDER-404');
    }
}
