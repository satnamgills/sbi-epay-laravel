<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use SbiEpay\SbiEpay\DTOs\PaymentRequest;
use SbiEpay\SbiEpay\DTOs\PaymentResponse;
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

it('initiates payment with account_identifier and user_details array', function () {
    $initiation = SbiEpay::initiate(new PaymentRequest(
        orderId: 'ORD_MULTI_1',
        amount: 1500.00,
        successUrl: 'https://app.test/pay/success',
        failUrl: 'https://app.test/pay/fail',
        accountIdentifier: 'MUNICIPALITY_COLLECTION_ACC',
        userDetails: [
            'consumer_id' => 'CNS101',
            'ward' => '4B',
        ],
    ));

    expect($initiation->accountIdentifier)->toBe('MUNICIPALITY_COLLECTION_ACC')
        ->and($initiation->encryptedMultiAccount)->toBeString();

    $decryptedMulti = SbiEpay::decrypt($initiation->encryptedMultiAccount);
    expect($decryptedMulti)->toBe('1500.00|INR|MUNICIPALITY_COLLECTION_ACC');

    $decryptedTrans = SbiEpay::decrypt($initiation->encryptedTrans);
    expect($decryptedTrans)->toContain('consumer_id:CNS101/ward:4B');
});

it('allows fluent account configuration overriding', function () {
    $custom = SbiEpay::account('WATER_DEPT_ACCOUNT');

    expect($custom->getAccountIdentifier())->toBe('WATER_DEPT_ACCOUNT');
});

it('verifies transaction using official SBI ePay double verification format', function () {
    $verificationUrl = SbiEpay::getVerificationUrl();

    // Official SBI manual sample from Page 17:
    // encData=5963046646141|SUCCESS|IN|INR|Other|Dgr9U|2|Completed Successfully|SBI |IG00FMKEX5|2014-11-20 12:23:23|IMPS|10000032015020365454 ||||||||||
    $officialResponse = '5963046646141|SUCCESS|IN|INR|dept:PropertyTax/bill:2026|ORD_OFFICIAL_1|2500.00|Completed Successfully|SBI|IG00FMKEX5|2014-11-20 12:23:23|IMPS|10000032015020365454';

    Http::fake([
        $verificationUrl => Http::response($officialResponse, 200),
    ]);

    $verification = SbiEpay::verify('ORD_OFFICIAL_1', 2500.00);

    expect($verification->isSuccess())->toBeTrue()
        ->and($verification->orderId)->toBe('ORD_OFFICIAL_1')
        ->and($verification->sbiTransactionId)->toBe('5963046646141')
        ->and($verification->amount)->toBe(2500.00)
        ->and($verification->status)->toBe('SUCCESS')
        ->and($verification->otherDetails)->toBe('dept:PropertyTax/bill:2026')
        ->and($verification->userDetails)->toBe([
            'dept' => 'PropertyTax',
            'bill' => '2026',
        ]);
});

it('verifies transaction using live SBI ePay double verification format with merchantId at token 0', function () {
    $verificationUrl = SbiEpay::getVerificationUrl();

    $liveSuccess = '1003947|2011578958428|SUCCESS|IN|INR|dept:Tax/bill:10|1234L6TDVXXB54|100.00|Completed Successfully|SBI|IG00FMKEX5|2026-10-30 12:00:00|NB|SBIEPAY|1003947|0.00^0.00';

    Http::fake([
        $verificationUrl => Http::response($liveSuccess, 200),
    ]);

    $verification = SbiEpay::verify('1234L6TDVXXB54', 100.00);

    expect($verification->isSuccess())->toBeTrue()
        ->and($verification->orderId)->toBe('1234L6TDVXXB54')
        ->and($verification->merchantId)->toBe('1003947')
        ->and($verification->sbiTransactionId)->toBe('2011578958428')
        ->and($verification->status)->toBe('SUCCESS')
        ->and($verification->amount)->toBe(100.00)
        ->and($verification->message)->toBe('Completed Successfully')
        ->and($verification->otherDetails)->toBe('dept:Tax/bill:10')
        ->and($verification->userDetails)->toBe(['dept' => 'Tax', 'bill' => '10']);
});

it('handles live SBI ePay No Records Found response gracefully', function () {
    $verificationUrl = SbiEpay::getVerificationUrl();

    $liveNoRecord = '1003947|NA|No Records Found|NA|NA|NA|1234L6TDVXXB54|NA|No Records Found|NA|NA|NA|NA|NA|1003947|0.00^0.00';

    Http::fake([
        $verificationUrl => Http::response($liveNoRecord, 200),
    ]);

    $verification = SbiEpay::verify('1234L6TDVXXB54', 100.00);

    expect($verification->isSuccess())->toBeFalse()
        ->and($verification->orderId)->toBe('1234L6TDVXXB54')
        ->and($verification->status)->toBe('NO RECORDS FOUND')
        ->and($verification->sbiTransactionId)->toBeNull()
        ->and($verification->message)->toBe('No Records Found');
});

it('verifies using a PaymentResponse instance directly', function () {
    $verificationUrl = SbiEpay::getVerificationUrl();

    $liveSuccess = '1003947|2011578958428|SUCCESS|IN|INR|dept:Tax/bill:10|1234L6TDVXXB54|100.00|Completed Successfully|SBI|IG00FMKEX5|2026-10-30 12:00:00|NB|SBIEPAY|1003947|0.00^0.00';

    Http::fake([
        $verificationUrl => Http::response($liveSuccess, 200),
    ]);

    // Create PaymentResponse (as returned by SbiEpay::handleCallback)
    $paymentResponse = new PaymentResponse(
        orderId: '1234L6TDVXXB54',
        status: 'SUCCESS',
        amount: 100.00,
        sbiTransactionId: '2011578958428',
        bankReferenceNo: 'IG00FMKEX5',
        bankName: 'SBI',
        transactionDate: '2026-10-30 12:00:00',
        merchantId: '1003947',
        rawResponse: '...',
    );

    $verification = SbiEpay::verify($paymentResponse);

    expect($verification->isSuccess())->toBeTrue()
        ->and($verification->orderId)->toBe('1234L6TDVXXB54')
        ->and($verification->amount)->toBe(100.00);
});

it('verifies using raw decrypted callback string decData directly', function () {
    $verificationUrl = SbiEpay::getVerificationUrl();

    $liveSuccess = '1003947|2011578958428|SUCCESS|IN|INR|dept:Tax/bill:10|1234L6TDVXXB54|100.00|Completed Successfully|SBI|IG00FMKEX5|2026-10-30 12:00:00|NB|SBIEPAY|1003947|0.00^0.00';

    Http::fake([
        $verificationUrl => Http::response($liveSuccess, 200),
    ]);

    // Format from double-verification.php ($data[0] = orderId, $data[13] = merchantId, $data[3] = amount)
    $decData = '1234L6TDVXXB54|SUCCESS|2011578958428|100.00|INR|Other|2026-10-30|NA|IG00FMKEX5|SBI|NA|NB|DOM|1003947';

    $verification = SbiEpay::verify($decData);

    expect($verification->isSuccess())->toBeTrue()
        ->and($verification->orderId)->toBe('1234L6TDVXXB54')
        ->and($verification->amount)->toBe(100.00);
});

it('handles gateway access denied / single string error response', function () {
    $verificationUrl = SbiEpay::getVerificationUrl();

    Http::fake([
        $verificationUrl => Http::response('Access Denied, Please contact Administrator.', 200),
    ]);

    $verification = SbiEpay::verify('ORD_ERR_1', 100.00);

    expect($verification->isSuccess())->toBeFalse()
        ->and($verification->orderId)->toBe('ORD_ERR_1')
        ->and($verification->status)->toBe('FAILED')
        ->and($verification->message)->toBe('Access Denied, Please contact Administrator.');
});
