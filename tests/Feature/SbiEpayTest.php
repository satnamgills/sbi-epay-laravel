<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use SbiEpay\SbiEpay\DTOs\PaymentRequest;
use SbiEpay\SbiEpay\Facades\SbiEpay;

beforeEach(function () {
    Config::set('sbi-epay.merchant_id', '1000003');
    Config::set('sbi-epay.merchant_key', '1234567890123456');
    Config::set('sbi-epay.mode', 'test');
});

it('initiates a payment and returns a PaymentInitiation DTO', function () {
    $initiation = SbiEpay::initiate(new PaymentRequest(
        orderId: 'ORD_101',
        amount: 500.0,
        successUrl: 'https://app.test/pay/success',
        failUrl: 'https://app.test/pay/fail',
    ));

    expect($initiation->orderId)->toBe('ORD_101')
        ->and($initiation->amount)->toBe(500.0)
        ->and($initiation->merchantId)->toBe('1000003')
        ->and($initiation->encryptedTrans)->toBeString()
        ->and($initiation->paymentUrl)->toContain('AggregatorHostedListener');

    // Test form data
    $formData = $initiation->formData();
    expect($formData)->toHaveKeys(['action', 'EncryptTrans', 'MultiAccountInstructionDtls', 'merchIdVal'])
        ->and($formData['merchIdVal'])->toBe('1000003');

    // Test redirect response view
    $response = $initiation->redirect();
    expect($response->getContent())->toContain('Redirecting to SBI ePay')
        ->and($response->getContent())->toContain($initiation->encryptedTrans);
});

it('supports initiation from array data', function () {
    $initiation = SbiEpay::initiate([
        'order_id' => 'ORD_102',
        'amount' => 750.50,
        'success_url' => 'https://app.test/pay/success',
        'fail_url' => 'https://app.test/pay/fail',
    ]);

    expect($initiation->orderId)->toBe('ORD_102')
        ->and($initiation->amount)->toBe(750.50);
});

it('decrypts and handles callback data into a PaymentResponse', function () {
    $rawCallback = 'ORD_101|SUCCESS|SBI998877|500.00|INR|Other|2026-10-14 12:00:00|IN|BNK123|STATE BANK OF INDIA|00|NB|ONLINE|1000003';
    $encrypted = SbiEpay::encrypt($rawCallback);

    $response = SbiEpay::handleCallback($encrypted);

    expect($response->isSuccess())->toBeTrue()
        ->and($response->orderId)->toBe('ORD_101')
        ->and($response->amount)->toBe(500.0)
        ->and($response->sbiTransactionId)->toBe('SBI998877')
        ->and($response->merchantId)->toBe('1000003');
});

it('verifies a transaction using double verification API', function () {
    $verificationUrl = SbiEpay::getVerificationUrl();

    Http::fake([
        $verificationUrl => Http::response('ORD_101|SUCCESS|500.00|SBI998877|00|SUCCESS', 200),
    ]);

    $verification = SbiEpay::verify('ORD_101', 500.00);

    expect($verification->isSuccess())->toBeTrue()
        ->and($verification->orderId)->toBe('ORD_101')
        ->and($verification->amount)->toBe(500.00)
        ->and($verification->status)->toBe('SUCCESS')
        ->and($verification->sbiTransactionId)->toBe('SBI998877');
});

it('allows dynamic merchant credentials overriding', function () {
    $custom = SbiEpay::merchant('9999999', 'abcdefabcdefabcd');

    expect($custom->getMerchantId())->toBe('9999999')
        ->and($custom->getMerchantKey())->toBe('abcdefabcdefabcd');
});
