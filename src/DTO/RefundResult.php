<?php

namespace rainwaves\PaystackPayment\DTO;

class RefundResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $reference,
        public readonly string $status,
        public readonly ?int $amountInMinor = null,
        public readonly ?string $currency = null,
        public readonly array $raw = [],
    ) {}
}
