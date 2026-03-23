<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use rainwaves\PaystackPayment\DTO\WebhookPayload;
use rainwaves\PaystackPayment\Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
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
}
