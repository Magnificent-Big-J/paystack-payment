<?php

namespace rainwaves\PaystackPayment\DTO;

class CustomerData
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $phone = null,
        public readonly array $metadata = [],
    ) {}
}
