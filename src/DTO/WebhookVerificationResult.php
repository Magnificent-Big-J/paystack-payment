<?php

namespace rainwaves\PaystackPayment\DTO;

class WebhookVerificationResult
{
    public function __construct(
        public readonly string $provider,
        public readonly bool $isValid,
        public readonly ?string $eventType = null,
        public readonly ?string $reference = null,
        public readonly array $payload = [],
    ) {}
}
