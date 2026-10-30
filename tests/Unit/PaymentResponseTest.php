<?php

declare(strict_types=1);

use SbiEpay\SbiEpay\DTOs\PaymentResponse;
use SbiEpay\SbiEpay\Exceptions\InvalidCallbackException;

it('parses a successful payment response string', function () {
    $raw = 'ORD_9999|SUCCESS|SBI_REF_123|1500.00|INR|Other|2026-10-14 11:30:00|IN|BANK_REF_789|STATE BANK OF INDIA|00|NB|ONLINE|M10001';

    $response = PaymentResponse::parse($raw);

    expect($response->isSuccess())->toBeTrue()
        ->and($response->isFailed())->toBeFalse()
        ->and($response->orderId)->toBe('ORD_9999')
        ->and($response->status)->toBe('SUCCESS')
        ->and($response->amount)->toBe(1500.00)
        ->and($response->sbiTransactionId)->toBe('SBI_REF_123')
        ->and($response->bankReferenceNo)->toBe('BANK_REF_789')
        ->and($response->bankName)->toBe('STATE BANK OF INDIA')
        ->and($response->merchantId)->toBe('M10001');
});

it('parses a failed payment response string', function () {
    $raw = 'ORD_9999|FAIL|SBI_REF_123|1500.00|INR|Other|2026-10-14 11:30:00|IN|BANK_REF_789|STATE BANK OF INDIA|00|NB|ONLINE|M10001';

    $response = PaymentResponse::parse($raw);

    expect($response->isSuccess())->toBeFalse()
        ->and($response->isFailed())->toBeTrue()
        ->and($response->status)->toBe('FAIL');
});

it('parses official SBI ePay manual callback format with user details', function () {
    // Official document sample from Page 10:
    // encData=Dgr9U|5963046646141|SUCCESS|2|INR|NB|Other|Completed Successfully|SBI|IG00FMKEX5|2014-11-20 12:23:23|IN|10000032015024911161||||||||||
    $raw = 'Dgr9U|5963046646141|SUCCESS|2500.00|INR|NB|dept:Water/ward:15/bill:OCT2026|Completed Successfully|SBI|IG00FMKEX5|2014-11-20 12:23:23|IN|10000032015024911161||||||||||';

    $response = PaymentResponse::parse($raw);

    expect($response->isSuccess())->toBeTrue()
        ->and($response->orderId)->toBe('Dgr9U')
        ->and($response->sbiTransactionId)->toBe('5963046646141')
        ->and($response->status)->toBe('SUCCESS')
        ->and($response->amount)->toBe(2500.00)
        ->and($response->currency)->toBe('INR')
        ->and($response->paymode)->toBe('NB')
        ->and($response->otherDetails)->toBe('dept:Water/ward:15/bill:OCT2026')
        ->and($response->message)->toBe('Completed Successfully')
        ->and($response->bankName)->toBe('SBI')
        ->and($response->bankReferenceNo)->toBe('IG00FMKEX5')
        ->and($response->transactionDate)->toBe('2014-11-20 12:23:23')
        ->and($response->cin)->toBe('10000032015024911161')
        ->and($response->userDetails)->toBe([
            'dept' => 'Water',
            'ward' => '15',
            'bill' => 'OCT2026',
        ]);
});

it('throws exception on malformed or empty response string', function () {
    PaymentResponse::parse('single_token_without_delimiter');
})->throws(InvalidCallbackException::class);
