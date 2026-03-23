<?php

namespace rainwaves\PaystackPayment\DTO;

class CustomerResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $code,
        public readonly string $email,
        public readonly array $raw = [],
    ) {}
}
