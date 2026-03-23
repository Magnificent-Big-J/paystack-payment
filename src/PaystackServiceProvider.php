<?php

namespace rainwaves\PaystackPayment;

use Illuminate\Http\Client\Factory as HttpFactory;
use rainwaves\PaystackPayment\Contracts\PaymentGatewayInterface;
use rainwaves\PaystackPayment\Drivers\Paystack\PaystackGateway;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PaystackServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('paystack')
            ->hasConfigFile('paystack');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PaymentGatewayInterface::class, function ($app) {
            $driver = (string) config('paystack.default', 'paystack');

            return match ($driver) {
                'paystack' => new PaystackGateway(
                    $app->make(HttpFactory::class),
                    (array) config('paystack.paystack', [])
                ),
                default => throw new \InvalidArgumentException("Unsupported payments driver [{$driver}]."),
            };
        });
    }
}
