<?php

namespace rainwaves\PaystackPayment\DTO;

class CheckoutInitializationData
{
    public function __construct(
        public readonly string $reference,
        public readonly string $email,
        public readonly int $amountInMinor,
        public readonly ?string $currency = null,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $planCode = null,
        public readonly ?string $customerCode = null,
        public readonly array $metadata = [],
    ) {}
}
