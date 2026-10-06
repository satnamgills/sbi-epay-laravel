<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SbiEpay\SbiEpay\SbiEpayServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SbiEpayServiceProvider::class,
        ];
    }
}
