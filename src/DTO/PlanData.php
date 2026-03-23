<?php

namespace rainwaves\PaystackPayment\DTO;

class PlanData
{
    public function __construct(
        public readonly string $name,
        public readonly int $amountInMinor,
        public readonly string $interval,
        public readonly ?string $currency = null,
        public readonly ?string $description = null,
        public readonly ?int $invoiceLimit = null,
        public readonly array $metadata = [],
    ) {}
}
