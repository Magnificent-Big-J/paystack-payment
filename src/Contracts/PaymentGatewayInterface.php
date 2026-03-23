<?php

namespace rainwaves\PaystackPayment\Contracts;

use rainwaves\PaystackPayment\DTO\CheckoutInitializationData;
use rainwaves\PaystackPayment\DTO\CheckoutInitializationResult;
use rainwaves\PaystackPayment\DTO\CustomerData;
use rainwaves\PaystackPayment\DTO\CustomerResult;
use rainwaves\PaystackPayment\DTO\PlanData;
use rainwaves\PaystackPayment\DTO\PlanResult;
use rainwaves\PaystackPayment\DTO\RefundData;
use rainwaves\PaystackPayment\DTO\RefundResult;
use rainwaves\PaystackPayment\DTO\TransactionVerificationResult;
use rainwaves\PaystackPayment\DTO\WebhookPayload;
use rainwaves\PaystackPayment\DTO\WebhookVerificationResult;

interface PaymentGatewayInterface
{
    public function provider(): string;

    public function createCustomer(CustomerData $customer): CustomerResult;

    public function createPlan(PlanData $plan): PlanResult;

    public function initializeCheckout(CheckoutInitializationData $checkout): CheckoutInitializationResult;

    public function verifyTransaction(string $reference): TransactionVerificationResult;

    public function createRefund(RefundData $refund): RefundResult;

    public function verifyWebhook(WebhookPayload $payload): WebhookVerificationResult;
}
