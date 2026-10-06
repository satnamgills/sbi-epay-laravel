<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SbiEpay\SbiEpay\SbiEpay
 */
class SbiEpay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SbiEpay\SbiEpay\SbiEpay::class;
    }
}
