<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use rainwaves\PaystackPayment\DTO\CustomerData;
use rainwaves\PaystackPayment\Exceptions\InvalidPaymentRequestException;
use rainwaves\PaystackPayment\Tests\TestCase;

class PaystackCustomerTest extends TestCase
{
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

        $result = $this->gateway(http: $http)->createCustomer(new CustomerData(
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

    public function test_it_rejects_invalid_customer_email(): void
    {
        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The customer email field must contain a valid email address.');

        $this->gateway()->createCustomer(new CustomerData(
            email: 'wrong-email',
        ));
    }
}
