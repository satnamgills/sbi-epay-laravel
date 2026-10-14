<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gateway Mode
    |--------------------------------------------------------------------------
    |
    | Supported modes: "test", "live".
    | When set to "test", the test environment endpoints are used by default.
    |
    */
    'mode' => env('SBI_EPAY_MODE', 'test'),

    /*
    |--------------------------------------------------------------------------
    | Merchant Credentials
    |--------------------------------------------------------------------------
    |
    | The Merchant ID and 16/32 byte Merchant Key provided by State Bank of India.
    |
    */
    'merchant_id' => env('SBI_EPAY_MERCHANT_ID', ''),
    'merchant_key' => env('SBI_EPAY_MERCHANT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    |
    | Override standard URLs or leave null to use mode defaults.
    |
    */
    'payment_url' => env('SBI_EPAY_PAYMENT_URL'),
    'verification_url' => env('SBI_EPAY_VERIFICATION_URL'),

    'endpoints' => [
        'test' => [
            'payment' => 'https://test.sbiepay.sbi/secure/AggregatorHostedListener',
            'verification' => 'https://test.sbiepay.sbi/secure/DoubleVerificationGateway',
        ],
        'live' => [
            'payment' => 'https://www.sbiepay.sbi/secure/AggregatorHostedListener',
            'verification' => 'https://www.sbiepay.sbi/secure/DoubleVerificationGateway',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Transaction Parameters
    |--------------------------------------------------------------------------
    |
    | Default values passed in the pipe-delimited payload to SBI ePay.
    |
    */
    'aggregator_id' => env('SBI_EPAY_AGGREGATOR_ID', 'SBIEPAY'),
    'operating_mode' => env('SBI_EPAY_OPERATING_MODE', '2'),
    'country' => env('SBI_EPAY_COUNTRY', 'IN'),
    'currency' => env('SBI_EPAY_CURRENCY', 'INR'),

];
