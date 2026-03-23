<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use rainwaves\PaystackPayment\DTO\RefundData;
use rainwaves\PaystackPayment\Exceptions\InvalidPaymentRequestException;
use rainwaves\PaystackPayment\Tests\TestCase;

class PaystackRefundTest extends TestCase
{
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

        $result = $this->gateway(http: $http)->createRefund(new RefundData(
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

    public function test_it_rejects_blank_refund_reference(): void
    {
        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The refund transaction reference field is required.');

        $this->gateway()->createRefund(new RefundData(
            transactionReference: ' ',
        ));
    }

    public function test_it_rejects_non_positive_refund_amount(): void
    {
        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The refund amount field must be greater than zero.');

        $this->gateway()->createRefund(new RefundData(
            transactionReference: 'ORDER-REFUND-INVALID',
            amountInMinor: 0,
        ));
    }
}
