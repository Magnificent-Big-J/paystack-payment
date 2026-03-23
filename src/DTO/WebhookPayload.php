<?php

namespace rainwaves\PaystackPayment\DTO;

class WebhookPayload
{
    public function __construct(
        public readonly string $rawBody,
        public readonly array $headers,
        public readonly array $payload,
    ) {}

    public function header(string $name): ?string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower((string) $key) !== $normalized) {
                continue;
            }

            if (is_array($value)) {
                return (string) ($value[0] ?? '');
            }

            return (string) $value;
        }

        return null;
    }
}
