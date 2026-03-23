<?php

namespace rainwaves\PaystackPayment\Tests;

use Illuminate\Http\Client\Factory as HttpFactory;
use PHPUnit\Framework\TestCase as BaseTestCase;
use rainwaves\PaystackPayment\Drivers\Paystack\PaystackGateway;

abstract class TestCase extends BaseTestCase
{
    protected function config(array $overrides = []): array
    {
        return array_replace([
            'secret_key' => 'sk_test_package',
            'public_key' => 'pk_test_package',
            'webhook_secret' => 'whsec_test_package',
            'base_url' => 'https://api.paystack.co',
            'currency' => 'ZAR',
            'timeout' => 15,
            'retry' => 0,
        ], $overrides);
    }

    protected function gateway(array $config = [], ?HttpFactory $http = null): PaystackGateway
    {
        return new PaystackGateway($http ?? new HttpFactory(), $this->config($config));
    }
}
