<?php

namespace rainwaves\PaystackPayment\DTO;

class RefundData
{
    public function __construct(
        public readonly string $transactionReference,
        public readonly ?int $amountInMinor = null,
        public readonly ?string $currency = null,
        public readonly ?string $reason = null,
        public readonly ?string $customerNote = null,
        public readonly ?string $merchantNote = null,
    ) {}
}
