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
        public readonly ?string $planCode = null,
        public readonly ?string $subscriptionCode = null,
        public readonly ?string $authorizationCode = null,
        public readonly array $raw = [],
    ) {}
}
