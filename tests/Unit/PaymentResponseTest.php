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

it('throws exception on malformed or empty response string', function () {
    PaymentResponse::parse('single_token_without_delimiter');
})->throws(InvalidCallbackException::class);
