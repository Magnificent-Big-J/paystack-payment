<?php

namespace rainwaves\PaystackPayment\DTO;

class TransactionVerificationResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $reference,
        public readonly string $status,
        public readonly ?int $amountInMinor = null,
        public readonly ?string $currency = null,
        public readonly ?string $customerCode = null,
        public readonly ?string $customerEmail = null,
        public readonly ?string $customerName = null,
        public readonly ?string $planCode = null,
        public readonly ?string $subscriptionCode = null,
        public readonly ?string $authorizationCode = null,
        public readonly ?bool $authorizationReusable = null,
        public readonly ?string $paidAt = null,
        public readonly ?int $feesInMinor = null,
        public readonly ?string $channel = null,
        public readonly ?string $gatewayResponse = null,
        public readonly array $raw = [],
    ) {}
}
