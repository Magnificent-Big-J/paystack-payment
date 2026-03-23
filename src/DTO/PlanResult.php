<?php

namespace rainwaves\PaystackPayment\DTO;

class PlanResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $code,
        public readonly string $name,
        public readonly string $interval,
        public readonly int $amountInMinor,
        public readonly string $currency,
        public readonly array $raw = [],
    ) {}
}
