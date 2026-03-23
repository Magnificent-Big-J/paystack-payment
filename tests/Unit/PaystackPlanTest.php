<?php

namespace rainwaves\PaystackPayment\Tests\Unit;

use Illuminate\Http\Client\Factory as HttpFactory;
use rainwaves\PaystackPayment\DTO\PlanData;
use rainwaves\PaystackPayment\Exceptions\InvalidPaymentRequestException;
use rainwaves\PaystackPayment\Tests\TestCase;

class PaystackPlanTest extends TestCase
{
    public function test_it_uses_the_configured_currency_for_plan_creation(): void
    {
        $http = new HttpFactory();
        $http->fake([
            'https://api.paystack.co/plan' => HttpFactory::response([
                'data' => [
                    'plan_code' => 'PLN_123',
                    'name' => 'Gold Plan',
                    'interval' => 'monthly',
                    'amount' => 9900,
                    'currency' => 'ZAR',
                ],
            ]),
        ]);

        $plan = $this->gateway(http: $http)->createPlan(new PlanData(
            name: 'Gold Plan',
            amountInMinor: 9900,
            interval: 'monthly',
        ));

        $request = $http->recorded()->map(fn (array $pair) => $pair[0])->all()[0];
        $this->assertSame('https://api.paystack.co/plan', $request->url());
        $this->assertSame('ZAR', $request->data()['currency']);
        $this->assertSame('PLN_123', $plan->code);
        $this->assertSame('ZAR', $plan->currency);
    }

    public function test_it_rejects_blank_plan_name(): void
    {
        $this->expectException(InvalidPaymentRequestException::class);
        $this->expectExceptionMessage('The plan name field is required.');

        $this->gateway()->createPlan(new PlanData(
            name: ' ',
            amountInMinor: 9900,
            interval: 'monthly',
        ));
    }
}
