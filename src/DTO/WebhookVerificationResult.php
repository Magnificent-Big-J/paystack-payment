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

    public function isEvent(string $eventType): bool
    {
        return $this->eventType === $eventType;
    }

    public function isChargeSuccess(): bool
    {
        return $this->isEvent('charge.success');
    }

    public function isSubscriptionCreate(): bool
    {
        return $this->isEvent('subscription.create');
    }

    public function isInvoicePaymentFailed(): bool
    {
        return $this->isEvent('invoice.payment_failed');
    }
}
