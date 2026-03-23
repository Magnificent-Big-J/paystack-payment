<?php

namespace rainwaves\PaystackPayment\Drivers\Paystack;

use Illuminate\Http\Client\Factory as HttpFactory;
use rainwaves\PaystackPayment\Contracts\PaymentGatewayInterface;
use rainwaves\PaystackPayment\DTO\CheckoutInitializationData;
use rainwaves\PaystackPayment\DTO\CheckoutInitializationResult;
use rainwaves\PaystackPayment\DTO\CustomerData;
use rainwaves\PaystackPayment\DTO\CustomerResult;
use rainwaves\PaystackPayment\DTO\PlanData;
use rainwaves\PaystackPayment\DTO\PlanResult;
use rainwaves\PaystackPayment\DTO\RefundData;
use rainwaves\PaystackPayment\DTO\RefundResult;
use rainwaves\PaystackPayment\DTO\TransactionVerificationResult;
use rainwaves\PaystackPayment\DTO\WebhookPayload;
use rainwaves\PaystackPayment\DTO\WebhookVerificationResult;
use rainwaves\PaystackPayment\Exceptions\InvalidPaymentRequestException;
use rainwaves\PaystackPayment\Exceptions\PaymentGatewayException;

class PaystackGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly array $config,
    ) {}

    public function provider(): string
    {
        return 'paystack';
    }

    public function createCustomer(CustomerData $customer): CustomerResult
    {
        $this->assertValidEmail($customer->email, 'customer email');

        $response = $this->client()->post('/customer', $this->filterPayload([
            'email' => $customer->email,
            'first_name' => $customer->firstName,
            'last_name' => $customer->lastName,
            'phone' => $customer->phone,
            'metadata' => $this->metadataObject($customer->metadata),
        ]));

        if ($response->failed()) {
            throw new PaymentGatewayException('Paystack customer creation failed: '.$response->body());
        }

        $data = (array) $response->json('data', []);

        return new CustomerResult(
            provider: $this->provider(),
            code: (string) ($data['customer_code'] ?? ''),
            email: (string) ($data['email'] ?? $customer->email),
            raw: (array) $response->json(),
        );
    }

    public function createPlan(PlanData $plan): PlanResult
    {
        $this->assertPositiveAmount($plan->amountInMinor, 'plan amount');
        $this->assertNotBlank($plan->name, 'plan name');
        $this->assertNotBlank($plan->interval, 'plan interval');
        $currency = $this->resolveCurrency($plan->currency);

        $response = $this->client()->post('/plan', $this->filterPayload([
            'name' => $plan->name,
            'amount' => $plan->amountInMinor,
            'interval' => $plan->interval,
            'currency' => $currency,
            'description' => $plan->description,
            'invoice_limit' => $plan->invoiceLimit,
        ]));

        if ($response->failed()) {
            throw new PaymentGatewayException('Paystack plan creation failed: '.$response->body());
        }

        $data = (array) $response->json('data', []);

        return new PlanResult(
            provider: $this->provider(),
            code: (string) ($data['plan_code'] ?? ''),
            name: (string) ($data['name'] ?? $plan->name),
            interval: (string) ($data['interval'] ?? $plan->interval),
            amountInMinor: (int) ($data['amount'] ?? $plan->amountInMinor),
            currency: (string) ($data['currency'] ?? $currency),
            raw: (array) $response->json(),
        );
    }

    public function initializeCheckout(CheckoutInitializationData $checkout): CheckoutInitializationResult
    {
        $this->assertPositiveAmount($checkout->amountInMinor, 'checkout amount');
        $this->assertNotBlank($checkout->reference, 'checkout reference');
        $this->assertValidEmail($checkout->email, 'checkout email');
        $currency = $this->resolveCurrency($checkout->currency);

        $response = $this->client()->post('/transaction/initialize', $this->filterPayload([
            'email' => $checkout->email,
            'amount' => $checkout->amountInMinor,
            'reference' => $checkout->reference,
            'currency' => $currency,
            'callback_url' => $checkout->callbackUrl,
            'plan' => $checkout->planCode,
            'customer' => $checkout->customerCode,
            'metadata' => $this->metadataJson($checkout->metadata),
        ]));

        if ($response->failed()) {
            throw new PaymentGatewayException(
                'Paystack checkout initialization failed: '.$response->body()
            );
        }

        $data = (array) $response->json('data', []);
        $checkoutUrl = (string) ($data['authorization_url'] ?? '');

        if ($checkoutUrl === '') {
            throw new PaymentGatewayException('Paystack checkout initialization returned no authorization URL.');
        }

        return new CheckoutInitializationResult(
            provider: $this->provider(),
            reference: (string) ($data['reference'] ?? $checkout->reference),
            checkoutUrl: $checkoutUrl,
            accessCode: isset($data['access_code']) ? (string) $data['access_code'] : null,
            raw: (array) $response->json(),
        );
    }

    public function verifyTransaction(string $reference): TransactionVerificationResult
    {
        $response = $this->client()->get('/transaction/verify/'.rawurlencode($reference));

        if ($response->failed()) {
            throw new PaymentGatewayException('Paystack transaction verification failed: '.$response->body());
        }

        $data = (array) $response->json('data', []);
        $customer = (array) ($data['customer'] ?? []);
        $plan = (array) ($data['plan_object'] ?? $data['plan'] ?? []);
        $subscription = (array) ($data['subscription'] ?? []);
        $authorization = (array) ($data['authorization'] ?? []);

        return new TransactionVerificationResult(
            provider: $this->provider(),
            reference: (string) ($data['reference'] ?? $reference),
            status: (string) ($data['status'] ?? 'unknown'),
            amountInMinor: isset($data['amount']) ? (int) $data['amount'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
            customerCode: isset($customer['customer_code']) ? (string) $customer['customer_code'] : null,
            customerEmail: isset($customer['email']) ? (string) $customer['email'] : null,
            customerName: $this->resolveCustomerName($customer),
            planCode: isset($plan['plan_code']) ? (string) $plan['plan_code'] : null,
            subscriptionCode: isset($subscription['subscription_code']) ? (string) $subscription['subscription_code'] : null,
            authorizationCode: isset($authorization['authorization_code']) ? (string) $authorization['authorization_code'] : null,
            authorizationReusable: isset($authorization['reusable']) ? (bool) $authorization['reusable'] : null,
            paidAt: isset($data['paid_at']) ? (string) $data['paid_at'] : null,
            feesInMinor: isset($data['fees']) ? (int) $data['fees'] : null,
            channel: isset($data['channel']) ? (string) $data['channel'] : null,
            gatewayResponse: isset($data['gateway_response']) ? (string) $data['gateway_response'] : null,
            raw: (array) $response->json(),
        );
    }

    public function createRefund(RefundData $refund): RefundResult
    {
        $this->assertNotBlank($refund->transactionReference, 'refund transaction reference');

        if ($refund->amountInMinor !== null) {
            $this->assertPositiveAmount($refund->amountInMinor, 'refund amount');
        }

        $response = $this->client()->post('/refund', array_filter([
            'transaction' => $refund->transactionReference,
            'amount' => $refund->amountInMinor,
            'currency' => $refund->currency,
            'merchant_note' => $refund->merchantNote ?? $refund->reason,
            'customer_note' => $refund->customerNote,
        ], fn ($value) => $value !== null && $value !== ''));

        if ($response->failed()) {
            throw new PaymentGatewayException('Paystack refund creation failed: '.$response->body());
        }

        $data = (array) $response->json('data', []);

        return new RefundResult(
            provider: $this->provider(),
            reference: (string) ($data['transaction']['reference'] ?? $refund->transactionReference),
            status: (string) ($data['status'] ?? 'pending'),
            amountInMinor: isset($data['amount']) ? (int) $data['amount'] : $refund->amountInMinor,
            currency: isset($data['currency']) ? (string) $data['currency'] : $refund->currency,
            raw: (array) $response->json(),
        );
    }

    public function verifyWebhook(WebhookPayload $payload): WebhookVerificationResult
    {
        $signature = (string) ($payload->header('x-paystack-signature') ?? '');
        $secret = (string) ($this->config['webhook_secret'] ?? $this->config['secret_key'] ?? '');

        $expected = hash_hmac('sha512', $payload->rawBody, $secret);
        $isValid = $signature !== '' && $secret !== '' && hash_equals($expected, $signature);
        $data = (array) ($payload->payload['data'] ?? []);

        return new WebhookVerificationResult(
            provider: $this->provider(),
            isValid: $isValid,
            eventType: isset($payload->payload['event']) ? (string) $payload->payload['event'] : null,
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            payload: $payload->payload,
        );
    }

    private function client()
    {
        return $this->http
            ->asJson()
            ->baseUrl(rtrim((string) ($this->config['base_url'] ?? 'https://api.paystack.co'), '/'))
            ->timeout((int) ($this->config['timeout'] ?? 15))
            ->retry((int) ($this->config['retry'] ?? 2), 200)
            ->acceptJson()
            ->withToken((string) ($this->config['secret_key'] ?? ''));
    }

    private function metadataObject(array $metadata): ?object
    {
        if ($metadata === []) {
            return null;
        }

        return json_decode(
            json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, 512),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    private function metadataJson(array $metadata): ?string
    {
        if ($metadata === []) {
            return null;
        }

        return json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function filterPayload(array $payload): array
    {
        return array_filter($payload, fn ($value) => $value !== null && $value !== []);
    }

    private function resolveCurrency(?string $currency = null): string
    {
        $resolved = strtoupper(trim((string) ($currency ?? $this->config['currency'] ?? '')));

        if ($resolved === '') {
            throw new PaymentGatewayException(
                'Paystack currency is required. Set paystack.paystack.currency or pass a currency per request.'
            );
        }

        return $resolved;
    }

    private function assertNotBlank(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidPaymentRequestException("The {$field} field is required.");
        }
    }

    private function assertValidEmail(string $email, string $field): void
    {
        $this->assertNotBlank($email, $field);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidPaymentRequestException("The {$field} field must contain a valid email address.");
        }
    }

    private function assertPositiveAmount(int $amountInMinor, string $field): void
    {
        if ($amountInMinor <= 0) {
            throw new InvalidPaymentRequestException("The {$field} field must be greater than zero.");
        }
    }

    private function resolveCustomerName(array $customer): ?string
    {
        $name = trim(implode(' ', array_filter([
            isset($customer['first_name']) ? (string) $customer['first_name'] : null,
            isset($customer['last_name']) ? (string) $customer['last_name'] : null,
        ])));

        return $name !== '' ? $name : null;
    }
}
