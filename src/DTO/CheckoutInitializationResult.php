<?php

namespace rainwaves\PaystackPayment\DTO;

class CheckoutInitializationResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $reference,
        public readonly string $checkoutUrl,
        public readonly ?string $accessCode = null,
        public readonly array $raw = [],
    ) {}
}
