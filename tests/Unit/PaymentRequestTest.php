<?php

declare(strict_types=1);

use SbiEpay\SbiEpay\DTOs\PaymentRequest;

it('formats the SBI ePay pipe-delimited payload properly', function () {
    $request = new PaymentRequest(
        orderId: 'ORD_12345',
        amount: 2500.50,
        successUrl: 'https://mysite.com/payments/sbi/success',
        failUrl: 'https://mysite.com/payments/sbi/fail',
        otherDetails: 'TaxPayment',
    );

    $pipePayload = $request->toPipePayload(
        merchantId: 'M10001',
        defaultAggregatorId: 'SBIEPAY',
        defaultOperatingMode: '2',
        defaultCountry: 'IN',
        defaultCurrency: 'INR',
    );

    expect($pipePayload)->toBe(
        'M10001|DOM|IN|INR|2500.50|TaxPayment|https://mysite.com/payments/sbi/success|https://mysite.com/payments/sbi/fail|SBIEPAY|ORD_12345|2|NB|ONLINE|ONLINE',
    );
});

it('creates instance from array', function () {
    $request = PaymentRequest::fromArray([
        'order_id' => 'ORD_888',
        'amount' => 100,
        'success_url' => 'https://site.com/s',
        'fail_url' => 'https://site.com/f',
    ]);

    expect($request->orderId)->toBe('ORD_888')
        ->and($request->amount)->toBe(100.0)
        ->and($request->currency)->toBe('INR')
        ->and($request->country)->toBe('IN')
        ->and($request->otherDetails)->toBe('Other');
});
