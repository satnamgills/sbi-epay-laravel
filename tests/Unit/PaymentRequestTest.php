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

it('serializes user_details array into OtherDetails and sanitizes delimiters', function () {
    $request = new PaymentRequest(
        orderId: 'ORD_999',
        amount: 1200.0,
        successUrl: 'https://site.com/s',
        failUrl: 'https://site.com/f',
        userDetails: [
            'department' => 'Water|Tax', // pipe must be sanitized
            'account_no' => 'ACC=9876', // equal must be sanitized
            'customer_name' => 'John Doe',
        ],
    );

    expect($request->otherDetails)->toBe('department:Water-Tax/account_no:ACC:9876/customer_name:John Doe')
        ->and($request->otherDetails)->not->toContain('|')
        ->and($request->otherDetails)->not->toContain('=');
});

it('builds multi_account instruction details from account_identifier', function () {
    $request = new PaymentRequest(
        orderId: 'ORD_1001',
        amount: 2500.50,
        successUrl: 'https://site.com/s',
        failUrl: 'https://site.com/f',
        currency: 'INR',
        accountIdentifier: 'MUNICIPALITY_COLLECTION_ACC',
    );

    expect($request->getMultiAccountDetails())->toBe('2500.50|INR|MUNICIPALITY_COLLECTION_ACC');
});

it('supports creating instance from array with account_identifier and user_details', function () {
    $request = PaymentRequest::fromArray([
        'order_id' => 'ORD_1002',
        'amount' => 350.00,
        'success_url' => 'https://site.com/s',
        'fail_url' => 'https://site.com/f',
        'account_identifier' => 'WATER_DEPT_01',
        'user_details' => [
            'ward' => '12',
            'zone' => 'North',
        ],
    ]);

    expect($request->accountIdentifier)->toBe('WATER_DEPT_01')
        ->and($request->userDetails)->toBe(['ward' => '12', 'zone' => 'North'])
        ->and($request->otherDetails)->toBe('ward:12/zone:North')
        ->and($request->getMultiAccountDetails())->toBe('350.00|INR|WATER_DEPT_01');
});
